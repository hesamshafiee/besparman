<?php

namespace App\Services\V1\Wallet;

use App\Jobs\SendTelegramMessageJob;
use App\Models\Order;
use App\Notifications\V1\MailSystem;
use App\Notifications\V1\SmsSystem;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Collection;

class WalletService
{
    private \App\Models\Wallet|null $wallet = null;
    private int|null $transaction_id = null;

    public string $type;
    public string $value;
    public string|null $originalPrice;
    public string|null $profit;
    public string|null $province;
    public string|null $city;
    public bool|null $mainPage;
    public int|null $groupId;
    public string|null $multipleTopupId;
    public string|null $webserviceCode;
    public string|null $productType;
    public string|null $productName;
    public string|null $userType;
    public string $detail;
    public string $status;
    public string|null $description = null;
    public int|null $orderId = null;
    public int|null $transferToId = null;
    public int|null $transferFromId = null;
    public int|null $userId = null;
    public ?array $extraInfo = null;
    public string|null $chargedMobile;

    /**
     * @param $type
     * @param $detail
     * @param $status
     * @param null $userId
     * @param null $orderId
     * @param null $extraInfo
     */
    public function __construct($type, $detail, $status, $userId = null, $orderId = null, $extraInfo = [])
    {
        $this->type = $type;
        $this->detail = $detail;
        $this->status = $status;
        $this->userId = $userId;
        $this->orderId = $orderId;

        $this->chargedMobile = $extraInfo['phone_number'] ?? null;
        $this->originalPrice = $extraInfo['price'] ?? null;
        $extraInfo['ip'] = (is_null(Request::ip()) || Request::ip() === '127.0.0.1') ? '-' : Request::ip();
        $this->extraInfo = $extraInfo;
    }

    /**
     * @return array
     */
    public function transaction(): array
    {
        return DB::transaction(function () {
            $this->getWallet($this->userId);

            $check = $this->check();
            if (!$check['status']) {
                return $check;
            }

            $walletValueAfterTransaction = $this->calculate();
            $walletTransaction = $this->newTransaction($walletValueAfterTransaction);

            if ($this->status == WalletTransaction::STATUS_CONFIRMED) {
                $this->wallet->value = $walletValueAfterTransaction;
            }

            if ($walletTransaction->save() && $this->wallet->save()) {
                if ($walletTransaction->status == WalletTransaction::STATUS_CONFIRMED) {
                    $message = $this->type == WalletTransaction::TYPE_INCREASE ? 'increaseConfirmation' : 'decreaseConfirmation';
//                    $this->notify($message, $this->wallet->user);

                }
                if ($this->wallet->user->telegramAccounts()->count()>0) {
                    $this->Telegram($this->wallet->user->telegramAccounts ?? collect());
                }
                $this->transaction_id = $walletTransaction->id;
                return ['status' => true, 'transaction_id' => $this->transaction_id, 'wallet_value_after_transaction' => $walletValueAfterTransaction];
            }
            return ['status' => false, 'error' => 'Transaction error'];
        });
    }

    /**
     * @param string $message
     * @param User|null $user
     * @return void
     */
    public function notify(string $message, User $user = null): void
    {
        $attributes = ['amount' => number_format($this->value), 'name' => User::nameOrMobile($user)];
        $user->notify(new SmsSystem(__('sms.' . $message, $attributes), 'force'));
        $user->notify(new MailSystem(__('email.' . $message, $attributes), 'force', __('email.' . $message . 'Subject')));
    }

    private function Telegram( Collection $telegram_ids): void
    {
        //$Minimum_wallet = $this->wallet->user->settings->settings['minimum_wallet'] ?? 10000000;
       
        if (0) {

             $message = '💰 کیف پول شما';

            foreach ($telegram_ids as $telegramAccount) {
                $telegram_id = $telegramAccount->telegram_id;
                 if ($telegram_id) {
                    SendTelegramMessageJob::dispatch($message, $telegram_id)->onQueue('high');
                }
            }
        }
    }

    /**
     * @param int|null $userId
     * @return void
     */
    public function getWallet(int $userId = null): void
    {
        if ($userId) {
            $user = User::find($userId);
            if ($user && is_null($user->wallet)) {
                $this->wallet = $user->wallet()->create();
            } elseif ($user) {
                $this->wallet = \App\Models\Wallet::where('user_id', $user->id)->lockForUpdate()->first();
            }
        } else {
            $wallet = \App\Models\Wallet::where('user_id', Auth::id())->lockForUpdate()->first();
            if (is_null($wallet)) {
                $wallet = Auth::user()->wallet()->create();
            }

            $this->wallet = $wallet;
        }
    }

    /**
     * @return array
     */
    private function check(): array
    {
        if (!$this->wallet) {
            return ['status' => false, 'error' => 'Problem in wallet, please contact support'];
        }

        if (($this->type === WalletTransaction::TYPE_DECREASE && $this->wallet->value < $this->value)) {
            return ['status' => false, 'error' => 'Not enough money /e3'];
        }

//        if ($this->orderId) {
//            $order = Order::find($this->orderId);
//            if ($order->status !== Order::STATUSRESERVED) {
//                return ['status' => false, 'error' => 'Wrong order status'];
//            }
//        }

        return ['status' => true];
    }

    /**
     * @return string|null
     */
    private function confirmedBy(): string|null
    {
        $confirmedBy = null;

        if ($this->status === WalletTransaction::STATUS_CONFIRMED) {
            if ($this->detail === WalletTransaction::DETAIL_DECREASE_ADMIN ||
                $this->detail === WalletTransaction::DETAIL_INCREASE_ADMIN) {
                $confirmedBy = Auth::user()->mobile . ' - ' . Auth::id() . ' - ' . now();
            } else {
                $confirmedBy = 'system' . ' / ' . now();
            }
        }

        return $confirmedBy;
    }

    /**
     * @param string $walletValueAfterTransaction
     * @return WalletTransaction
     */
    private function newTransaction(string $walletValueAfterTransaction): WalletTransaction
    {
        $walletTransaction = new WalletTransaction();

        $walletTransaction->value = abs($this->value);
        if (isset($this->profit)) {
            $walletTransaction->profit = abs($this->profit);
        }
        $walletTransaction->type = $this->type;
        $walletTransaction->resnumber = WalletTransaction::walletTransactionNumber();
        $walletTransaction->wallet_id = $this->wallet->id;
        $walletTransaction->wallet_value_after_transaction = $walletValueAfterTransaction;
        $walletTransaction->user_id = $this->wallet->user_id;
        $walletTransaction->status = $this->status;
        $walletTransaction->detail = $this->detail;
        $walletTransaction->order_id = $this->orderId;
        $walletTransaction->extra_info = $this->extraInfo;
        $walletTransaction->original_price = $this->originalPrice;
        $walletTransaction->province = $this->province ?? null;
        $walletTransaction->city = $this->city ?? null;
        $walletTransaction->main_page = $this->mainPage ?? false;
        $walletTransaction->webservice_code = empty($this->webserviceCode) ? null : $this->webserviceCode;
        $walletTransaction->user_type = $this->userType ?? null;
        $walletTransaction->product_type = $this->productType ?? null;
        $walletTransaction->product_name = $this->productName ?? null;
        $walletTransaction->confirmed_by = $this->confirmedBy();
        $walletTransaction->description = $this->description;
        $walletTransaction->transfer_from_id = is_null($this->transferFromId) ? null : Auth::id();
        $walletTransaction->transfer_to_id = $this->transferToId;
        $walletTransaction->sign = $walletTransaction->sign();

        return $walletTransaction;
    }

    /**
     * @return string
     */
    private function calculate(): string
    {
        return $this->type == WalletTransaction::TYPE_INCREASE ? bcadd($this->wallet->value, $this->value, 4) : bcsub($this->wallet->value, $this->value, 4);
    }
}
