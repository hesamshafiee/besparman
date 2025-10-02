<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTopupJob;
use App\Models\Operator;
use App\Models\Product;
use App\Models\ScheduledTopup;
use App\Models\User;
use App\Services\V1\Esaj\EsajService;
use App\Services\V1\Esaj\Irancell;
use App\Services\V1\Wallet\Wallet;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as status;
use Symfony\Component\HttpFoundation\Response;

class purchaseWithoutCartController extends Controller
{
    private string $requiredWebserviceCode = '';
    private Authenticatable $user;
    private Product $product;
    private string $maxPrice;
    private string $minPrice;

    private string $price;

    private string|null $takenValue;

    private string $webserviceCode;

    private bool $fakeResponse;

    private string $returnUrl;

    private string|null $discountCode;
    private string $mobile;
    private bool $mainPage;
    private string|null $operatorType;
    private string|null $offerCode;
    private string|null $offerType;

    private string|null $multipleId = null;
    private ?string $scheduledAt = null;

    /**
     * @param Request $request`
     * @return JsonResponse
     * @group purchaseWithoutCart
     */
    public function topUp(Request $request): JsonResponse
    {
        $this->init($request);
        $response = $this->buy();

        if (array_key_exists('token', $response) || $response['status']) {
            return response()->json($response, status::HTTP_OK);
        }

        return $this->throwError($response['error'] ?? 'Something went wrong', $response['code'] ?? 500);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkTopUp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'topups' => ['required', 'array', 'min:1'],
            'topups.*.product_id' => ['required', 'numeric'],
            'topups.*.mobile' => ['required', 'numeric', 'digits_between:12,12'],
            'topups.*.main_page' => ['boolean'],
            'topups.*.price' => ['required', 'numeric'],
            'topups.*.taken_value' => ['numeric'],
            'topups.*.operator_type' => ['string'],
            'topups.*.ext_id' => ['numeric', 'in:59,19'],
            'topups.*.webservice_code' => ['string'],
            'topups.*.fake_response' => ['boolean'],
            'topups.*.return_url' => ['string'],
            'topups.*.discount_code' => ['string'],
            'topups.*.offerCode' => ['string'],
            'topups.*.offerType' => ['string'],
            'topups.*.counter' => ['numeric', 'min:1'],
        ]);

        $results = [];
        $reservationNumber = date("YmdHis") . random_int(1000, 9999);


        foreach ($data['topups'] as $topupRequest) {
            $counter = $topupRequest['counter'] ?? 1;

            for ($i = 0; $i < $counter; $i++) {
                try {
                    $singleRequest = new Request($topupRequest);

                    $this->init($singleRequest);
                    $response = $this->buy($reservationNumber);

                    $results[] = [
                        'status' => true,
                        'data' => $response,
                        'product_id' => $topupRequest['product_id'],
                        'mobile' => $topupRequest['mobile'],
                        'iteration' => $i + 1,
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'status' => false,
                        'message' => $e->getMessage(),
                        'product_id' => $topupRequest['product_id'],
                        'mobile' => $topupRequest['mobile'],
                        'iteration' => $i + 1,
                    ];
                }
            }
        }

        return response()->json([
            'status' => true,
            'results' => $results
        ], Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @group purchaseWithoutCart
     */
    public function irancellBill(Request $request)
    {
        $validated = $request->validate([
            'mobile' => ['required', 'numeric', 'digits_between:12,12']
        ]);

        $esajService = new EsajService();
        $esajService->setGateway(new Irancell());
        $response = $esajService->getBillInquiry($validated['mobile']);

        return response()->json($response, status::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @group purchaseWithoutCart
     */
    public function irancellOffers(Request $request)
    {
        $validated = $request->validate([
            'mobile' => ['required', 'numeric', 'digits_between:12,12']
        ]);

        $esajService = new EsajService();
        $esajService->setGateway(new Irancell());
        $response = $esajService->getOfferPackage($validated['mobile']);

        return response()->json($response, status::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @group purchaseWithoutCart
     */
    public function irancellSimType(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mobile' => ['required', 'numeric', 'digits_between:12,12']
        ]);

        $esajService = new EsajService();
        $esajService->setGateway(new Irancell());
        $response = $esajService->getSimType($validated['mobile']);

        if (isset($response['subscriber_type']) && !empty($response['subscriber_type'])) {
            return response()->json($response['subscriber_type'] === 'Postpaid' ? Product::SIM_CARD_TYPE_PERMANENT : Product::SIM_CARD_TYPE_CREDIT, status::HTTP_OK);
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @group purchaseWithoutCart
     */
    public function PackageList(Request $request): JsonResponse
    {
        $this->authorize('update', Operator::class);

        $validated = $request->validate([
            'operator' => ['required', 'string', 'in:aptel,rightel,shatel,mci,irancell']
        ]);

        $operator = "App\Services\V1\Esaj\\" . ucfirst($validated['operator']);

        $esajService = new EsajService();
        $esajService->setGateway(new $operator());
        $response = $esajService->packageList();

        return response()->json($response, status::HTTP_OK);
    }


    /**
     *
     * @return void
     * @group purchaseWithoutCart
     */
    public function getBalance()
    {
        $this->authorize('update', Operator::class);


        $reminingOfOperators = Operator::getRemaining();

        return response()->json(['data' => $reminingOfOperators], 200);
    }

    /**
     * @param Request $request
     * @return void
     * @group purchaseWithoutCart
     */
    private function init(Request $request): void
    {
        $preValidation = $request->validate([
            'product_id' => ['required', 'numeric'],
            'mobile' => ['required', 'numeric', 'digits_between:12,12'],
            'main_page' => ['boolean'],
            'scheduled_at' => ['date', 'after:now']
        ]);

        $this->mobile = $preValidation['mobile'];
        $this->mainPage = $preValidation['main_page'] ?? false;
        $this->scheduledAt = $preValidation['scheduled_at'] ?? null;

        $this->user = User::getLoggedInUserOrGetFromGivenMobile($preValidation['mobile']);

        $this->requiredWebserviceCode = '';
        if (empty($preValidation['main_page']) && $this->user->isWebservice()) {
            $this->requiredWebserviceCode = 'required';
        }

        $this->product = Product::where(function ($query) use ($preValidation) {
            $query->where('id', $preValidation['product_id']);

            if ($this->user->isWebservice()) {
                $query->orWhere('profile_id', $preValidation['product_id']);
            }
        })->firstOrFail();

        $this->minPrice = 'min:' . $this->product->price;
        $this->maxPrice = (is_null($this->product->second_price) || $this->product->price > $this->product->second_price) ? 'max:' . $this->product->price + ($this->product->price * 0.10)  : 'max:' . $this->product->second_price;


        if ($request->webservice_code) {
            $request->merge([
                'webservice_code' => $this->user->id . '-' . $request->webservice_code,
            ]);
        }

        $validated = $request->validate([
            'price' => ['required', 'numeric', $this->minPrice, $this->maxPrice],
            'taken_value' => ['numeric'],
            'operator_type' => ['string'],
            'ext_id' => ['numeric', 'in:59,19'],
            'webservice_code' => [$this->requiredWebserviceCode, 'string', 'unique:wallet_transactions'],
            'fake_response' => ['boolean'],
            'return_url' => ['string'],
            'discount_code' => ['string'],
            'offerCode' => ['string'],
            'offerType' => ['string']
        ]);


        $operatorType = Operator::getOperatorType($this->product);
        $this->operatorType = $operatorType ?? 0;
        $this->price = $validated['price'];
        $this->takenValue = $validated['taken_value'] ?? null;
        $this->webserviceCode = $validated['webservice_code'] ?? '';
        $this->fakeResponse = $validated['fake_response'] ?? false;
        $this->returnUrl = $validated['return_url'] ?? '';
        $this->discountCode = $validated['discount_code'] ?? null;
        $this->offerCode = $validated['offerCode'] ?? '';
        $this->offerType = $validated['offerType'] ?? '';
    }

    /**
     * @param string $message
     * @param int $code
     * @return JsonResponse
     * @group purchaseWithoutCart
     */
    private function throwError(string $message = 'Something went wrong / 0', int $code = 500): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            ], $code);
    }


    private function buy(string $multipleTopupId = null)
    {
        $payload = [
            $this->product,
            $this->mobile,
            $this->price,
            $this->offerCode,
            $this->offerType,
            $this->takenValue,
            $this->operatorType,
            59,
            $this->webserviceCode,
            $this->fakeResponse,
            $this->returnUrl,
            '',
            $this->mainPage,
            $this->discountCode,
            null,
            $multipleTopupId
        ];

        if ($this->scheduledAt) {
            $payload[11] = auth()->user()->mobile;
            $scheduled = ScheduledTopup::create([
                'user_id'     => $this->user->id,
                'scheduled_at'=> $this->scheduledAt,
                'payload'     => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'status'      => 'pending',
            ]);

            ProcessTopupJob::dispatch($scheduled->id)->onQueue('scheduled-topup')->delay(Carbon::parse($this->scheduledAt));

            return [
                'status'  => true,
                'message' => 'Top-up scheduled successfully',
                'data'    => [
                    'user_id'      => $scheduled->user_id,
                    'scheduled_at' => $scheduled->scheduled_at,
                    'payload'      => json_decode($scheduled->payload, true),
                    'status'       => $scheduled->status,
                    'updated_at'   => $scheduled->updated_at,
                    'created_at'   => $scheduled->created_at,
                    'id'           => $scheduled->id,
                ],
            ];
        }

        return Wallet::payWithoutCart(...$payload);
    }
}
