<?php

namespace App\Services\V1\Wallet;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\V1\Cart\Cart;
use App\Services\V1\Financial\Financial;
use App\Services\V1\Payment\PaymentService;
use App\Services\V1\Payment\Saman;
use Illuminate\Support\Facades\Auth;

class Director
{
    private Builder $transfer;
    private Builder $rejectTransfer;
    private Builder $confirmTransfer;
    private Builder $cardToCard;
    private Builder $increaseByAdmin;
    private Builder $increaseByBank;
    private Builder $decreaseByBank;
    private Builder $increaseByRefund;
    private Builder $decreaseByAdmin;
    private Builder $pay;
    private Builder $payCardCharge;
    private Builder $payWithoutCart;
    private Builder $increaseByPrize;

    /**
     * @param Builder $transfer
     * @param Builder $rejectTransfer
     * @param Builder $confirmTransfer
     * @param Builder $cardToCard
     * @param Builder $increaseByAdmin
     * @param Builder $decreaseByAdmin
     * @param Builder $pay
     * @param Builder $payCardCharge
     * @param Builder $payWithoutCart
     * @param Builder $increaseByBank
     * @param Builder $decreaseByBank
     * @param Builder $increaseByRefund
     * @param Builder $increaseByPrize
     */
    public function __construct(Builder $transfer,
                                Builder $rejectTransfer,
                                Builder $confirmTransfer,
                                Builder $cardToCard,
                                Builder $increaseByAdmin,
                                Builder $decreaseByAdmin,
                                Builder $pay,
                                Builder $payCardCharge,
                                Builder $payWithoutCart,
                                Builder $increaseByBank,
                                Builder $decreaseByBank,
                                Builder $increaseByRefund,
                                Builder $increaseByPrize,
    ) {
        $this->transfer = $transfer;
        $this->rejectTransfer = $rejectTransfer;
        $this->confirmTransfer = $confirmTransfer;
        $this->cardToCard = $cardToCard;
        $this->increaseByAdmin = $increaseByAdmin;
        $this->increaseByBank = $increaseByBank;
        $this->decreaseByAdmin = $decreaseByAdmin;
        $this->pay = $pay;
        $this->payCardCharge = $payCardCharge;
        $this->payWithoutCart = $payWithoutCart;
        $this->decreaseByBank = $decreaseByBank;
        $this->increaseByRefund = $increaseByRefund;
        $this->increaseByPrize = $increaseByPrize;
    }

    /**
     * @param string $value
     * @param int $transferToId
     * @return array
     */
    public function transfer(string $value, int $transferToId): array
    {
        return $this->transfer->execute(['value' => $value, 'transferToId' => $transferToId]);
    }

    /**
     * @param Payment $payment
     * @return array
     */
    public function cardToCard(Payment $payment): array
    {
        return $this->cardToCard->execute(['value' => $payment->price, 'status' => $payment->status]);
    }

    /**
     * @param Payment $payment
     * @return array
     */
    public function increaseByBank(Payment $payment): array
    {
        return $this->increaseByBank->execute(['value' => $payment->price, 'status' => $payment->status, 'userId' => $payment->user_id, 'orderId' => $payment->order_id]);
    }

    /**
     * @param Payment $payment
     * @return array
     */
    public function decreaseByBank(Payment $payment): array
    {
        return $this->decreaseByBank->execute(['payment' => $payment]);
    }

    /**
     * @param WalletTransaction $transaction
     * @return array
     */
    public function increaseByRefund(WalletTransaction $transaction): array
    {
        return $this->increaseByRefund->execute(['transaction' => $transaction]);
    }

    /**
     * @param string $value
     * @param int $userId
     * @param string $message
     * @return array
     */
    public function increaseByAdmin(string $value, int $userId, string $message): array
    {
        return $this->increaseByAdmin->execute(['value' => $value, 'user-id' => $userId, 'description' => $message]);
    }


    public function increaseByPrize(string $value, int $userId, string $message): array
    {
        return $this->increaseByPrize->execute(['value' => $value, 'user-id' => $userId, 'description' => $message]);
    }

    /**
     * @param string $value
     * @param int $userId
     * @param string $message
     * @return array
     */
    public function decreaseByAdmin(string $value, int $userId, string $message): array
    {
        return $this->decreaseByAdmin->execute(['value' => $value, 'user-id' => $userId, 'description' => $message]);
    }

    /**
     * @param string $returnUrl
     * @param string $bank
     * @return array
     */
    public function pay(string $returnUrl, string $bank): array
    {
        $cartInstanceName = 'cart';
        $cartObj = Cart::instance($cartInstanceName, null);
        $cartData = $cartObj->all();
        if (empty($cartData['items'])) {
            return ['status' => false, 'error' => 'Nothing in cart'];
        }
        $orderResponse = Financial::createOrder(Order::STATUSRESERVED, $cartInstanceName);
        $order = $orderResponse['order'];

        if ($order && $orderResponse['status'] && !empty($returnUrl) && !empty($bank)) {
            $bank = ucfirst($bank);
            $bankName = 'App\Services\V1\Payment\\' . $bank;
            $paymentService = new PaymentService();
            $paymentService->setGateway(new $bankName);
            return $paymentService->increase($order->final_price, Payment::TYPE_ONLINE, '', $bank, $returnUrl, $order->id);
        }

        if ($orderResponse['status']) {
            Delivery::createDelivery($order->id);
            return $this->pay->execute(['value' => $order->final_price, 'order' => $order,
                'takenValue' => 0, 'webserviceCode' => '', 'cartInstanceName' => $cartInstanceName]);
        }

        return ['status' => false, 'error' => 'Nothing in cart or order problem'];
    }



    public function payCardCharge(string $takenValue, string $webserviceCode, string $cartInstanceName = 'esaj'): array
    {
        $cartObj = Cart::instance($cartInstanceName, null);
        $cartData = $cartObj->all();
        if (empty($cartData['items'])) {
            return ['status' => false, 'error' => 'Nothing in cart'];
        }
        $orderResponse = Financial::createOrder(Order::STATUSRESERVED, $cartInstanceName);
        $order = $orderResponse['order'];
        if ($orderResponse['status']) {
            return $this->payCardCharge->execute(['value' => $order->final_price, 'order' => $order,
                'takenValue' => $takenValue, 'webserviceCode' => $webserviceCode, 'cartInstanceName' => $cartInstanceName]);
        }

        return ['status' => false, 'error' => 'Nothing in cart or order problem'];
    }

    /**
     * @param Product $product
     * @param string $mobile
     * @param string $price
     * @param $offerCode
     * @param $offerType
     * @param null $takenValue
     * @param $type
     * @param $ext_id
     * @param $webserviceCode
     * @param bool $fakeResponse
     * @param string $returnUrl
     * @param string $userMobile
     * @param bool $mainPage
     * @param string|null $discountCode
     * @param int|null $groupId
     * @return array
     */

    public function payWithoutCart(
        Product $product,
        string $mobile,
        string $price,
        $offerCode,
        $offerType,
        $takenValue,
        $type,
        $ext_id,
        $webserviceCode,
        bool $fakeResponse,
        string $returnUrl,
        string $userMobile,
        bool $mainPage,
        string|null $discountCode,
        int|null $groupId,
        string|null $multipleTopupId,
    ): array
    {
        if (empty($userMobile) && empty($mainPage) && !Auth::check()) {
            return ['status' => false, 'code' => 401, 'error' => 'You must login first'];
        }

        $mobileOfUser = empty($userMobile) ? $mobile : $userMobile;

        $user = User::getLoggedInUserOrGetFromGivenMobile($mobileOfUser);

        if ($user->isAdminOrEsaj()) {
            return ['status' => false, 'message' => 'You do not have access to this feature as admin', 'code' => 500];
        }

        if (
            $product->type !== Product::TYPE_CELL_DIRECT_CHARGE &&
            $product->type !== Product::TYPE_CELL_AMAZING_DIRECT_CHARGE &&
            $product->type !== Product::TYPE_CELL_INTERNET_PACKAGE &&
            $product->type !== Product::TYPE_TD_LTE_INTERNET_PACKAGE
        ) {
            return ['status' => false, 'message' => 'Product type not valid', 'code' => 500];
        }

        if ($product->private && !$user->private) {
            return ['status' => false, 'error' => 'This product can not be purchased / p0'];
        }

        if (!$product->status) {
            return ['status' => false, 'error' => 'Product is inactive / d1'];
        }

        $settingName = $product->sim_card_type . '_' . $product->type;

        if (!$product->operator || !$product->operator->status || !$product->operator->setting->$settingName) {
            return ['status' => false, 'error' => 'No Operator or status is inactive / d1'];
        }

        $detail = json_encode([
            'product_id' => $product->id,
            'mobile' => $mobile,
            'takenValue' => $takenValue,
            'type' => $type,
            'ext_id' => $ext_id,
            'offerCode' => $offerCode,
            'offerType' => $offerType,
            'webserviceCode' => $webserviceCode,
            'fakeResponse' => $fakeResponse,
            'userMobile' => $userMobile,
            'mainPage' => $mainPage,
            'groupId' => $groupId,
            ]);

        $orderResponse = Financial::createOrder(Order::STATUSRESERVED, 'esaj', $product, $mobileOfUser, $price, $detail, $discountCode, $mainPage);
        $order = $orderResponse['order'] ?? null;
        if ($order && $mainPage && empty($userMobile) && (!Auth::check() || ($user->isOrdinary() && (empty($user->wallet) || $user->wallet->value < $price)))) {
            $paymentService = new PaymentService();
            $paymentService->setGateway(new Saman());
            return $paymentService->increase($price, Payment::TYPE_ONLINE, $mobile, 'Saman', $returnUrl, $order->id);
        }

        if ($order && $product->status) {
            return $this->payWithoutCart->execute([
                'value' => $order->final_price,
                'order' => $order,
                'product' => $product,
                'mobile' => $mobile,
                'takenValue' => $takenValue,
                'type' => $type,
                'ext_id' => $ext_id,
                'offerCode' => $offerCode,
                'offerType' => $offerType,
                'webserviceCode' => $webserviceCode,
                'fakeResponse' => $fakeResponse,
                'userMobile' => $userMobile,
                'mainPage' => $mainPage,
                'groupId' => $groupId,
                'multipleTopupId' => $multipleTopupId
            ]);
        }

        return ['status' => false, 'error' => $order['message'] ?? 'product or order problem'];
    }

    /**
     * @param Order $order
     * @param bool $topup
     * @return array
     */
    public function continueAfterBank(Order $order, $topup = false): array
    {
        if ($topup) {
            $detail = json_decode($order->detail, true);
            $product = Product::find($detail['product_id']);

            if ($product->status) {
                return $this->payWithoutCart->execute([
                    'value' => $order->final_price,
                    'order' => $order,
                    'product' => $product,
                    'mobile' => $detail['mobile'],
                    'takenValue' => $detail['takenValue'],
                    'type' => $detail['type'],
                    'ext_id' => $detail['ext_id'],
                    'offerCode' => $detail['offerCode'],
                    'offerType' => $detail['offerType'],
                    'webserviceCode' => $detail['webserviceCode'],
                    'fakeResponse' => filter_var($detail['fakeResponse'], FILTER_VALIDATE_BOOLEAN),
                    'mainPage' => $detail['mainPage'],
                    'groupId' => null
                ]);
            }
        } else {
            Delivery::createDelivery($order->id);
            return $this->pay->execute(['value' => $order->final_price, 'order' => $order,
                'takenValue' => 0, 'webserviceCode' => '', 'cartInstanceName' => 'cart']);
        }

        return ['status' => false, 'error' => 'product or order problem'];
    }

    /**
     * @param int $transactionId
     * @param string $message
     * @return array
     */
    public function rejectTransfer(int $transactionId, string $message): array
    {
        return $this->rejectTransfer->execute(['transaction-id' => $transactionId, 'message' => $message]);
    }

    /**
     * @param int $transactionId
     * @return array
     */
    public function confirmTransfer(int $transactionId): array
    {
        return $this->confirmTransfer->execute(['transaction-id' => $transactionId]);
    }
}
