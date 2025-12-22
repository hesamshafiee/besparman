<?php

namespace App\Services\V1\Financial;

use App\Models\Delivery;
use App\Models\Discount;
use App\Models\Logistic;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Point;
use App\Models\PointHistory;
use App\Models\Product;
use App\Models\Profit;
use App\Models\ProfitSplit;
use App\Models\Report;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\V1\Payment\PaymentService;
use App\Services\V1\Wallet\Wallet;
use App\Services\V1\Wallet\WalletService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinancialService
{


    /**
     * @param string $status
     * @param string $store
     * @param Product|null $product
     * @param string $mobile
     * @param string|null $price
     * @param string|null $detail
     * @param string|null $discountCode
     * @param bool $mainPage
     * @return array
     */
    public function createOrder(string $status = Order::STATUSRESERVED, string $store = 'esaj', Product $product = null, string $mobile = '', string $price = null, string $detail = null, string $discountCode = null, bool $mainPage = false): array
    {
        $delivery = null;
        $deliveryPrice = 0;
        $user = User::getLoggedInUserOrGetFromGivenMobile($mobile);

        if ($product) {

            if (empty($price)) {
                $price = $product->price;
            }

            $finalPrice = $price;
            $totalDiscount = 0;
            $discountId = null;

            if (!empty($discountCode)) {
                $discount = $this->discountCheck($discountCode, $user->id, $product->id, $mainPage, $price);

                if ($discount['status']) {
                    $finalPrice = $price - $discount['value'];
                    $totalDiscount = $discount['value'];
                    $discountId = $discount['id'];
                } else {
                    return ['status' => false, 'message' => $discount['message']];
                }
            }


            $totalSale = 0;
            $saleId = null;
            $orderItems['product'][$product->id] = ['quantity' => 1, 'discount' => 0, 'price' => $finalPrice];
        } else {
            $cart = \App\Services\V1\Cart\Cart::instance($store);
            $checkout = $cart->all();

            if (!$checkout) {
                return ['status' => false, 'message' => 'Empty Cart'];
            }

            $delivery = $cart->getDelivery();

            if ($delivery) {
                $logistic = Logistic::find($delivery['logisticId']);

                if ($logistic) {
                    $deliveryPrice = $logistic->price;
                } else {
                    return ['status' => false, 'message' => 'empty delivery'];
                }
            }

            $checkout = $checkout['details'];
            $price = $checkout['price'];
            $finalPrice = $checkout['finalPrice'] + $deliveryPrice;
            $totalDiscount = $checkout['totalDiscount'];
            $discountId = $checkout['discountId'];
            $totalSale = $checkout['totalSale'];
            $saleId = $checkout['saleId'];
            $orderItems = $checkout['orderItems'];
        }

        $order = new Order();
        $order->user_id = $user->id;
        $order->status = $status;
        $order->store = $store;
        $order->price = $price;
        $order->final_price = $finalPrice;
        $order->total_discount = $totalDiscount;
        $order->discount_id = $discountId;
        $order->total_sale = $totalSale;
        $order->sale_id = $saleId;
        $order->delivery_price = $deliveryPrice;
        $order->detail = $detail;

        return DB::transaction(function () use ($order, $orderItems, $delivery) {
            if ($order->save()) {
                if ($delivery) {
                    $deliveryModel = new Delivery();
                    $deliveryModel->date = $delivery['date'];
                    $deliveryModel->delivery_between_start = $delivery['deliveryBetweenStart'];
                    $deliveryModel->delivery_between_end = $delivery['deliveryBetweenEnd'];
                    $deliveryModel->logistic_id = $delivery['logisticId'];
                    $deliveryModel->order_id = $order->id;
                    $deliveryModel->title = $delivery['title'];
                    $deliveryModel->province = $delivery['province'];
                    $deliveryModel->city = $delivery['city'];
                    $deliveryModel->address = $delivery['address'];
                    $deliveryModel->postal_code = $delivery['postal_code'];
                    $deliveryModel->phone = $delivery['phone'];
                    $deliveryModel->mobile = $delivery['mobile'];
                    $deliveryModel->save();
                }

                $pivotData = [];

                foreach ($orderItems['product'] as $productId => $item) {
                    $pivotData[$productId] = [
                        'quantity' => $item['quantity'],
                        'discount' => $item['discount'] ?? 0,
                        'price' => $item['price'],

                        // این دوتا رو خودمون json می‌کنیم
                        'product_snapshot' => isset($item['product_snapshot'])
                            ? json_encode($item['product_snapshot'])
                            : null,

                        'config' => isset($item['config'])
                            ? json_encode($item['config'])
                            : null,
                    ];
                }

                $order->products()->attach($pivotData);
                return ['status' => true, 'order' => $order];
            }

            return ['status' => false, 'message' => 'Error in saving order'];
        });
    }



    /**
     * @param Order $order
     * @param string $message
     * @return array
     */
    public function cancellingOrder(Order $order, string $message): array
    {
        $order->status = Order::STATUSCANCELED;

        $orderDetail = json_decode($order->detail, true) ?? [];
        $orderDetail['error_message'] = $message;

        $order->detail = json_encode($orderDetail);
        $order->save();

        return ['status' => false, 'error' => $message];
    }


    /**
     * @param float $esajPrice
     * @param float $esajProfit
     * @param Order $order
     * @param Product|null $product
     * @param bool $mainPage
     * @param int|null $groupId
     * @param array|null $thirdPartyInfo
     * @param bool|null $thirdPartyStatus
     * @param string|null $multipleTopupId
     * @return bool
     */
    public function handleEsajProfit(
        float $esajPrice,
        float $esajProfit,
        Order $order,
        ?Product $product = null,
        bool $mainPage = false,
        ?int $groupId = null,
        ?array $thirdPartyInfo = null,
        ?bool $thirdPartyStatus = null,
        ?string $multipleTopupId = null,
    ): bool {
        if ($esajPrice <= 0) {
            return true;
        }

        $esajUser = User::where('mobile', User::MOBILE_ADMIN)->first();
        if (!$esajUser) {
            return false;
        }

        $walletServiceEsaj = new WalletService(
            WalletTransaction::TYPE_INCREASE,
            WalletTransaction::DETAIL_INCREASE_PURCHASE_ESAJ,
            WalletTransaction::STATUS_CONFIRMED,
            $esajUser->id,
            $order->id
        );

        $walletServiceEsaj->value = $esajPrice;
        $walletServiceEsaj->profit = $esajProfit;
        $walletServiceEsaj->productType = $product->type ?? Product::TYPE_CARD_CHARGE;
        $walletServiceEsaj->productName = $product
            ? ($product->name ?? '') . ' - ' .
            ($product->category_name ? $product->category_name . ' - ' : '') .
            (($product->sim_card_type === Product::SIM_CARD_TYPE_CREDIT) ? 'اعتباری' :
                (($product->sim_card_type === Product::SIM_CARD_TYPE_PERMANENT) ? 'دائمی' : ''))
            : '';
        $walletServiceEsaj->mainPage = $mainPage;
        $walletServiceEsaj->groupId = $groupId;
        $walletServiceEsaj->multipleTopupId = $multipleTopupId;
        $walletServiceEsaj->userType = User::TYPE_PICBOOM;
        $walletServiceEsaj->third_party_info = $thirdPartyInfo;
        $walletServiceEsaj->third_party_status = $thirdPartyStatus;

        return $walletServiceEsaj->transaction()['status'] ?? false;
    }

    /**
     * @param float $buyerPrice
     * @param float $buyerProfit
     * @param User $user
     * @param Order $order
     * @param float $originalPrice
     * @param string|null $takenValue
     * @param bool $mainPage
     * @param Product|null $product
     * @param string|null $mobile
     * @param int|null $groupId
     * @param string|null $webserviceCode
     * @param string|null $multipleTopupId
     * @return bool
     */
    public function handleBuyerTransaction(
        float $buyerPrice,
        float $buyerProfit,
        User $user,
        Order $order,
        float $originalPrice,
        ?string $takenValue,
        bool $mainPage = false,
        ?Product $product = null,
        ?string $mobile = null,
        ?int $groupId = null,
        ?string $webserviceCode = null,
        ?string $multipleTopupId = null
    ): bool {
        if ($buyerPrice <= 0) {
            return true;
        }


        $extraInfo = [
            'phone_number' => $mobile,
            'phone_book_name' => '',
            'price' => $originalPrice,
            'taken_value' => $takenValue,
            'product_id' => $product->id ?? null,
            'product_name' => $product->name ?? null,
            'profile_id' => $product->profile_id ?? null,
        ];

        $walletServiceBuyer = new WalletService(
            WalletTransaction::TYPE_DECREASE,
            WalletTransaction::DETAIL_DECREASE_PURCHASE_BUYER,
            WalletTransaction::STATUS_CONFIRMED,
            $user->id,
            $order->id,
            $extraInfo
        );

        $walletServiceBuyer->value = $buyerPrice;
        $walletServiceBuyer->profit = $buyerProfit;
        $walletServiceBuyer->userType = $user->type;
        $walletServiceBuyer->province = optional($user->profile)->province;
        $walletServiceBuyer->city = optional($user->profile)->city;
        $walletServiceBuyer->mainPage = $mainPage;
        $walletServiceBuyer->groupId = $groupId;
        $walletServiceBuyer->multipleTopupId = $multipleTopupId;
        $walletServiceBuyer->productType = $product->type ?? Product::TYPE_CARD_CHARGE;
        $walletServiceBuyer->productName = $product
            ? ($product->name ?? '') . ' - ' .
            ($product->category_name ? $product->category_name . ' - ' : '') .
            (($product->sim_card_type === Product::SIM_CARD_TYPE_CREDIT) ? 'اعتباری' :
                (($product->sim_card_type === Product::SIM_CARD_TYPE_PERMANENT) ? 'دائمی' : ''))
            : '';
        $walletServiceBuyer->webserviceCode = $webserviceCode;

        $transaction = $walletServiceBuyer->transaction();
        if ($transaction['status']) {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     * @param Product $product
     * @param Order $order
     * @return bool
     */
    public function calculateUserPoints(User $user, Product $product, Order $order): bool
    {
        if ($user->isPanel()) {
            $point = Point::where('type', $product->type)
                ->where('status', 1)
                ->first();
            if ($point) {
                $points = (int) bcdiv($order->final_price, $point->value) * $point->point;

                if ($points > 0) {
                    $user->points += $points;
                    if ($user->save()) {
                        PointHistory::create([
                            'user_id' => $user->id,
                            'product_id' => $product->id,
                            'point' => $points,
                            'type' => $product->type,
                            'product_name' => $product->name,
                        ]);
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param string|null $discountCode
     * @param int $userId
     * @param int $productId
     * @param bool $mainPage
     * @param string $price
     * @return array
     */
    private function discountCheck(string|null $discountCode, int $userId, int $productId, bool $mainPage, string $price): array
    {
        if (empty($discountCode)) {
            return [
                'status' => false,
                'message' => 'No discount code'
            ];
        }

        if (!$mainPage) {
            return [
                'status' => false,
                'message' => 'Not from main page'
            ];
        }

        $discount = Discount::where('code', $discountCode)->where('status', Discount::STATUS_ACTIVE)->first();

        if (!$discount) {
            return [
                'status' => false,
                'message' => __('general.discountInvalid')
            ];
        }

        if (!$discount->reusable) {
            $usedDiscounts = Order::where('status', Order::STATUSPAID)
                ->where('user_id', $userId)->where('discount_id', $discount->id)->count();
            if ($usedDiscounts) {
                return [
                    'status' => false,
                    'message' => __('general.reusableDiscountError')
                ];
            }
        }

        if (!is_null($discount->expire_at) && $discount->expire_at < now()) {
            return [
                'status' => false,
                'message' => __('general.discountInvalidTime')
            ];
        }

        if ($discount->users()->count()) {
            if (!in_array($userId, $discount->users->pluck('id')->toArray())) {
                return [
                    'status' => false,
                    'message' => __('general.discountNotAble')
                ];
            }
        }

        if ($discount->products()->count()) {
            if (in_array($productId, $discount->products->pluck('id')->toArray())) {
                return [
                    'status' => false,
                    'message' => __('general.discountNotAble')
                ];
            }
        }

        if ($price < $discount['min_purchase']) {
            return [
                'status' => false,
                'message' => 'Not enough purchase'
            ];
        }

        if ($discount->type === Discount::TYPE_PERCENT) {
            $discountValue = ($discount->value / 100) * $price;
        } else {
            $discountValue = $discount->value;
        }

        if ($discount->max_purchase) {
            $discountValue = min($discountValue, $discount->max_purchase);
        }

        return [
            'status' => true,
            'id' => $discount->id,
            'value' => $discountValue,
        ];

    }
}
