<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Financial\PaymentRequest;
use App\Http\Resources\V1\OrderResource;
use App\Http\Resources\V1\PaymentResource;
use App\Models\Order;
use App\Models\Payment;
use App\Services\V1\Image\Image;
use App\Services\V1\Payment\CardToCard;
use App\Services\V1\Payment\PaymentService;
use App\Services\V1\Wallet\Wallet;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as status;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class PaymentController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Payment
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('show', Payment::class);

        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro(new PaymentResource(Payment::findOrFail($id)));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(PaymentResource::collection(Payment::orderBy($order, $typeOrder)->paginate($perPage)));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @group Payment
     */
    public function clientIndex(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);
        if ($id) {
            return response()->jsonMacro( new PaymentResource(Payment::where('user_id', Auth::id())->where('id', $id)->firstOrFail()));
        }

        $order = $request->query('order', 'id');
        $typeOrder = $request->query('type_order', 'desc');
        $perPage = (int) $request->query('per_page', 10);

        $allowedColumns = ['id', 'created_at', 'updated_at'];
        if (!in_array($order, $allowedColumns)) {
            $order = 'id';
        }


        if (!in_array(strtolower($typeOrder), ['asc', 'desc'])) {
            $typeOrder = 'desc';
        }

        return response()->jsonMacro(PaymentResource::collection(Payment::where('user_id', Auth::id())->orderBy($order, $typeOrder)->paginate($perPage)));
    }



    /**
     * @return JsonResponse
     * @throws 
     * @group Payment
     */
    public function orders(): JsonResponse
    {
        $this->authorize('show', Payment::class);

        return response()->jsonMacro(PaymentResource::collection(Order::orderByDesc('created_at')->paginate(100)));
    }

    /**
     * @param PaymentRequest $request
     * @return JsonResponse
     */
    public function cardToCard(PaymentRequest $request): JsonResponse
    {
        if (Auth::user()->isAdminOrEsaj() || Auth::user()->isPanelOrWebservice()) {
            return response()->serverError('You do not have access to this feature');
        }

        $paymentService = new PaymentService();
        $paymentService->setGateway(new CardToCard());
        $payment = $paymentService->increase($request->value, Payment::TYPE_CARD);

        if ($payment) {
            $response = Image::modelImages($payment, $request->file('image'), Image::DRIVER_LOCAL);
            if ($response) {
                return response()->ok(__('card to card requested'));
            }
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Payment $payment
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Payment
     */
    public function confirm(Payment $payment): JsonResponse
    {
        $this->authorize('confirm', Payment::class);

        $paymentService = new PaymentService();
        $paymentService->setGateway(new CardToCard());
        if ($paymentService->confirm($payment)) {
            return response()->ok(__('card to card confirmed'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Payment $payment
     * @return JsonResponse
     * @throws AuthorizationException
     * @group Payment
     */
    public function reject(Payment $payment): JsonResponse
    {
        $this->authorize('reject', Payment::class);

        $paymentService = new PaymentService();
        $paymentService->setGateway(new CardToCard());
        if ($paymentService->reject($payment)) {
            return response()->ok(__('card to card rejected'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param PaymentRequest $request
     * @return JsonResponse
     * @group Payment
     */
    public function bank(PaymentRequest $request): JsonResponse
    {
        if (Auth::check() && (Auth::user()->isAdminOrEsaj() || !Auth::user()->profile_confirm)) {
            return response()->serverError('You do not have access to this feature');
        }

        $bankName = ucfirst($request->get('bank', 'saman'));

        $bank = 'App\Services\V1\Payment\\' . $bankName;

        $paymentService = new PaymentService();
        $paymentService->setGateway(new $bank());
        $response = $paymentService->increase($request->value, Payment::TYPE_ONLINE, $request->mobile, $bankName, $request->return_url);

        if (is_array($response)) {
            return response()->json([
                $response
            ], status::HTTP_OK);
        }

        return response()->serverError(__('general.somethingWrong'));
    }


    /**
     * @param Request $request
     * @return RedirectResponse
     * @group Payment
     */
    public function callbackFromBank(Request $request): RedirectResponse
    {
        $payment = null;
        $respond = false;
        $returnUrl = env('REDIRECT_TO_FRONT_AFTER_BANK');
        $res = $request->ResNum ?? $request->SaleOrderId;

        if (!is_null($request->RefNum)) {
            $payment = Payment::where('refnumber', $request->RefNum)->first();
        }

        if (is_null($payment)) {
            $payment = Payment::where('resnumber', $res)->where('status', Payment::STATUSUNPAID)->firstOrFail();
        }

        if ($payment) {
            $bank = 'App\Services\V1\Payment\\' . ucfirst($payment->bank_name);
            $paymentService = new PaymentService();
            $paymentService->setGateway(new $bank());
            $info = $request->toArray();
            $respond = $paymentService->confirm($payment, $info);

            if (!empty($payment->order_id) && $respond) {
                $order = Order::where('id', $payment->order_id)->first();
                if ($order) {
                    $response = Wallet::continueAfterBank($order);
                }
            }

            $returnUrl = $payment->return_url;
        }
        $lastCharacter = substr($returnUrl, -1);

        $mark = $lastCharacter === '/' ? '?' : '&';

        $url = $returnUrl . $mark . 'ok=' . !!$respond .  '&refCode=' . $res;

        return Redirect::away($url);
    }
}
