<?php

namespace App\Services\V1\Wallet;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\ProfitGroup;
use App\Models\Variant;
use App\Services\V1\Cart\Cart;
use App\Services\V1\Financial\Financial;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class PayBuilder implements Builder
{
    private Order $order;
    private User $esajUser;
    private array $ips;

    private string $takenValue;

    private string $cartInstanceName;

    private Authenticatable|user $user;
    private $originalPrice;
    private string $webserviceCode;


    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        $this->initialize($data);
        $result = $this->check();
        if ($result !== true) {
            return $result;
        }

        return DB::transaction(function () {
            if ($this->allTransactions()) {
                $this->order->status = Order::STATUSPAID;
                if ($this->order->save()) {

                    Cart::instance($this->cartInstanceName)->flush();
                    return ['status' => true, 'order_id' => $this->order->id];
                }
            }

            return ['status' => false, 'error' => __('general.somethingWrong')];
        });
    }

    /**
     * @return bool
     */
    private function allTransactions(): bool
    {
        $profitGroup = $this->user->profitGroup ?: ProfitGroup::orderBy('id')->first();
        if (!$profitGroup) {
            return false;
        }

        $order = $this->order->load(['products.variant.category']);
        $adminUser = User::where('mobile', User::MOBILE_ADMIN)->first();
        if (!$adminUser) {
            return false;
        }

        $baseTotal = '0';
        $addTotal = '0';
        $deliveryTotal = (string) ($order->delivery_price ?? 0);
        $designerAmounts = [];
        $adminAmount = '0';
        $lockedVariants = [];

        foreach ($order->products as $product) {
            $qty = $product->pivot->quantity ?? 1;
            $variant = Variant::where('id', $product->variant_id)->lockForUpdate()->with('category')->first();
            if (!$variant) {
                return false;
            }
            if ($variant->stock < $qty) {
                return false;
            }
            $lockedVariants[$variant->id] = $variant;
            $category = $variant?->category;
            $basePrice = (string) ($category->base_price ?? 0);
            $addPrice = (string) ($variant?->add_price ?? 0);

            $baseSubtotal = bcmul($basePrice, $qty, 4);
            $addSubtotal = bcmul($addPrice, $qty, 4);

            $baseTotal = bcadd($baseTotal, $baseSubtotal, 4);
            $addTotal = bcadd($addTotal, $addSubtotal, 4);

            $designerShare = bcmul($addSubtotal, bcdiv($profitGroup->designer_profit, 100, 4), 4);
            $adminShareFromAdd = bcsub($addSubtotal, $designerShare, 4);

            if (!empty($product->user_id) && bccomp($designerShare, '0', 4) === 1) {
                $designerAmounts[$product->user_id] = bcadd($designerAmounts[$product->user_id] ?? '0', $designerShare, 4);
            } else {
                $adminShareFromAdd = $addSubtotal;
            }

            $adminAmount = bcadd($adminAmount, $adminShareFromAdd, 4);
        }

        $adminAmount = bcadd($adminAmount, $baseTotal, 4);
        $adminAmount = bcadd($adminAmount, $deliveryTotal, 4);

        $buyerTotal = bcadd($baseTotal, bcadd($addTotal, $deliveryTotal, 4), 4);

        $transaction = new WalletService(
            WalletTransaction::TYPE_DECREASE,
            WalletTransaction::DETAIL_DECREASE_PURCHASE_BUYER,
            WalletTransaction::STATUS_CONFIRMED,
            $this->user->id,
            $this->order->id
        );


        $transaction->value = $buyerTotal;
        $transaction->userType = $this->user->type;
        $transaction->province = optional($this->user->profile)->province;
        $transaction->city = optional($this->user->profile)->city;
        $transaction->mainPage = false;
        $transaction->productName = 'فروشگاه';
        $transaction->productType = '';

        $transactionResult = $transaction->transaction();


        if (!$transactionResult['status']) {
            return false;
        }

        if (bccomp($adminAmount, '0', 4) === 1) {
            $adminWallet = new WalletService(
                WalletTransaction::TYPE_INCREASE,
                WalletTransaction::DETAIL_INCREASE_PURCHASE_ESAJ,
                WalletTransaction::STATUS_CONFIRMED,
                $adminUser->id,
                $this->order->id
            );
            $adminWallet->value = $adminAmount;
            $adminWallet->productName = 'فروشگاه';
            $adminWallet->mainPage = false;

            $adminResult = $adminWallet->transaction();
            if (!$adminResult['status']) {
                return false;
            }
        }

        foreach ($designerAmounts as $designerId => $amount) {
            if (bccomp($amount, '0', 4) !== 1) {
                continue;
            }

            $designerWallet = new WalletService(
                WalletTransaction::TYPE_INCREASE,
                WalletTransaction::DETAIL_INCREASE_PURCHASE_PRESENTER,
                WalletTransaction::STATUS_CONFIRMED,
                $designerId,
                $this->order->id
            );

            $designerWallet->value = $amount;
            $designerWallet->productName = 'فروشگاه';
            $designerWallet->mainPage = false;

            $designerResult = $designerWallet->transaction();
            if (!$designerResult['status']) {
                return false;
            }
        }

        foreach ($order->products as $product) {
            $qty = $product->pivot->quantity ?? 1;
            $variant = $lockedVariants[$product->variant_id] ?? null;
            if (!$variant) {
                return false;
            }
            $variant->stock = bcsub((string) $variant->stock, (string) $qty, 0);
            if ($variant->stock < 0 || !$variant->save()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array|true
     */
    private function check(): array|true
    {
        $wallet = $this->user->wallet;
        if (!$wallet) {
            $wallet = $this->user->wallet()->create();
        }

        if ($wallet->value < $this->order->final_price) {
            return Financial::cancellingOrder($this->order, 'Not enough money / e1');
        }

        if (!empty($this->ips) && $this->user->isWebservice() && !in_array(Request::ip(), $this->ips)) {
            return Financial::cancellingOrder($this->order, 'IP check');
        }

        return true;
    }

    /**
     * @param array $data
     * @return void
     */
    private function initialize(array $data): void
    {
        $this->order = $data['order'];
        $this->user = Auth::user();
        $profile = $this->user->profile;
        $this->ips = empty($profile->ips) ? [] : explode('-', $profile->ips);
        $this->esajUser = User::where('mobile', User::MOBILE_ADMIN)->first();
        $this->originalPrice = $data['value'];
        $this->takenValue = $data['takenValue'] ?? $this->originalPrice;
        $this->webserviceCode = $data['webserviceCode'] ?? null;
        $this->cartInstanceName = $data['cartInstanceName'] ?? 'esaj';
    }
}
