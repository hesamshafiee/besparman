<?php

namespace App\Services\V1\Wallet;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Warehouse;
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
                    foreach ($this->order->products as $product) {
                        if($product->type === Product::TYPE_CART) {
                            $warehouse = Warehouse::where('product_id', $product->id)->lockForUpdate()->first();
                           if (!$warehouse || $warehouse->count < $product->pivot->quantity) {
                                throw new \Exception("There is not enough stock available for product {$product->id}.");
                            }
                            if ($warehouse) {
                                $warehouse->count -= $product->pivot->quantity;
                                $warehouse->save();
                            }
                        }
                    }

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
        $transaction = new WalletService(
            WalletTransaction::TYPE_DECREASE,
            WalletTransaction::DETAIL_DECREASE_PURCHASE_BUYER,
            WalletTransaction::STATUS_CONFIRMED,
            $this->user->id,
            $this->order->id
        );


        $transaction->value = $this->order->final_price;
        $transaction->userType = $this->user->type;
        $transaction->province = optional($this->user->profile)->province;
        $transaction->city = optional($this->user->profile)->city;
        $transaction->mainPage = false;
        $transaction->productName = 'فروشگاه';
        $transaction->productType = Product::TYPE_CART;

        $transactionResult = $transaction->transaction();


        if ($transactionResult['status']) {
            return true;
        }



        return false;
    }

    /**
     * @return array|true
     */
    private function check(): array|true
    {
        $wallet = $this->user->wallet;
        if (!$wallet) {
            return Financial::cancellingOrder($this->order, 'Problem with wallet please contact support / e1');
        } elseif ($wallet->value < $this->order->final_price) {
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
