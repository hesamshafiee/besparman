<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\DeliveryRequest;
use App\Models\Logistic;
use App\Models\Product;
use App\Services\V1\Cart\Cart;
use App\Services\V1\Wallet\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as status;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * @param string|null $cart
     * @return JsonResponse
     * @group Cart
     */
    public function index(string $cart = null) : JsonResponse
    {
        return response()->json([
            'cart' => Cart::instance('cart', $cart)->all(),
        ], status::HTTP_OK)->header('balance', optional(Auth::user())->wallet->value ?? 0);
    }

    /**
     * @param Product $product
     * @param string|null $cart
     * @return JsonResponse
     * @group Cart
     */
    public function addToCart(Product $product, string $cart = null) : JsonResponse
    {

         if ($product->type != Product::TYPE_CART) {
            return response()->json([
            'message' => __('general.productTypeNotAllowed'),
            ], status::HTTP_BAD_REQUEST);
        }

        $cartObj = Cart::instance('cart', $cart);
        $cartResponse = $cartObj->addToCart($product, $count = 1);

        return response()->json([
            'message' => $cartResponse['status'] ? __('general.addedToCart', ['id' => $product->id]) : $cartResponse['message'],
            'cart_key' => Auth::check() ? '' : $cartObj->cartKey,
            'cart' => Cart::instance('cart', $cart)->all()
        ], $cartResponse['status'] ? status::HTTP_OK : status::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @param Product $product
     * @param string|null $cart
     * @return JsonResponse
     * @group Cart
     */
    public function removeFromCart(Product $product, string $cart = null) : JsonResponse
    {
        $cartObj = Cart::instance('cart', $cart);
        $response = $cartObj->delete($product);
        return response()->json([
            'message' => $response ? __('general.deletedFromCart', ['id' => $product->id]) :  __('general.somethingWrong'),
            'cart_key' => $cartObj->cartKey,
            'cart' => Cart::instance('cart', $cart)->all()
        ], $response ? status::HTTP_OK : status::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @param string $discount
     * @return JsonResponse
     * @group Cart
     */
    public function addDiscount(string $discount = '') : JsonResponse
    {
        $cartObj = Cart::instance('cart', null);
        $response = $cartObj->addDiscount($discount);

        return response()->json([
            'message' => $response['message'],
            'cart_key' => $cartObj->cartKey,
            'cart' => Cart::instance('cart', $cartObj->cartKey)->all()
        ],$response['status'] ? status::HTTP_OK : status::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @group Cart
     */
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'returnUrl' => ['required_with:bank', 'string'],
            'bank'      => ['required_with:returnUrl', 'string', 'in:saman,mellat'],
        ]);

        $returnUrl = $validated['returnUrl'] ?? '';
        $bank = $validated['bank'] ?? '';

        $response = Wallet::pay($returnUrl, $bank);

        if (isset($response['status']) && $response['status']) {
            return response()->ok(__('checkout successfully'));
        } elseif (array_key_exists('token', $response)) {
            return response()->json([
                'token' => $response['token'],
            ]);
        } else {
            return response()->serverError($response['error']);
        }
    }

    /**
     * @param DeliveryRequest $request
     * @param Logistic $logistic
     * @return JsonResponse
     * @group Cart
     */
    public function createDelivery(DeliveryRequest $request, logistic $logistic): JsonResponse
    {
        if ($logistic->checkTime($request->deliveryBetweenStart, $request->deliveryBetweenEnd)) {
            $cartObj = Cart::instance('cart', null);
            $address = Auth::user()->addresses()->find($request->address_id);
            if (!$address) {
                return response()->serverError(__('general.somethingWrong'));
            }
            $response = $cartObj->addDelivery(
                $logistic->id,
                $request->date,
                $request->deliveryBetweenStart,
                $request->deliveryBetweenEnd,
                $address->id,
                $address->title,
                $address->province,
                $address->city,
                $address->address,
                $address->postal_code,
                $address->phone,
                $address->mobile
            );


            return response()->json(['status' => $response],$response ? status::HTTP_OK : status::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->serverError(__('general.somethingWrong'));
    }
}
