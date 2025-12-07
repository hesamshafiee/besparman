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

    /**
     * @param Builder $transfer
     * @param Builder $rejectTransfer
     * @param Builder $confirmTransfer
     * @param Builder $cardToCard
     * @param Builder $increaseByAdmin
     * @param Builder $decreaseByAdmin
     * @param Builder $pay
     * @param Builder $increaseByBank
     * @param Builder $decreaseByBank
     * @param Builder $increaseByRefund
     */
    public function __construct(Builder $transfer,
                                Builder $rejectTransfer,
                                Builder $confirmTransfer,
                                Builder $cardToCard,
                                Builder $increaseByAdmin,
                                Builder $decreaseByAdmin,
                                Builder $pay,
                                Builder $increaseByBank,
                                Builder $decreaseByBank,
                                Builder $increaseByRefund,
    ) {
        $this->transfer = $transfer;
        $this->rejectTransfer = $rejectTransfer;
        $this->confirmTransfer = $confirmTransfer;
        $this->cardToCard = $cardToCard;
        $this->increaseByAdmin = $increaseByAdmin;
        $this->increaseByBank = $increaseByBank;
        $this->decreaseByAdmin = $decreaseByAdmin;
        $this->pay = $pay;
        $this->decreaseByBank = $decreaseByBank;
        $this->increaseByRefund = $increaseByRefund;
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
    public function pay(string $returnUrl, string $bank, ?string $cartKey = null): array
    {
        $cartInstanceName = 'cart';
        $cartObj = Cart::instance($cartInstanceName, $cartKey);
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



    /**
     * @param Order $order
     * @param bool $topup
     * @return array
     */
    public function continueAfterBank(Order $order, $topup = false): array
    {

            Delivery::createDelivery($order->id);
            return $this->pay->execute(['value' => $order->final_price, 'order' => $order,
                'takenValue' => 0, 'webserviceCode' => '', 'cartInstanceName' => 'cart']);
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
