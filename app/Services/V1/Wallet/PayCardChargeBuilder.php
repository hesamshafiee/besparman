<?php

namespace App\Services\V1\Wallet;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\V1\Cart\Cart;
use App\Services\V1\Financial\Financial;
use App\Services\V1\Wallet\PostPurchaseActionFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class PayCardChargeBuilder implements Builder
{
    private Order $order;
    private User $esajUser;
    private string $esajPrice;
    private string $buyerPrice;
    private string $esajprofit;
    private string $buyerProfit;
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
                    //$this->walletService->notify('coursePaid', $this->order->user);
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
        $items = Cart::instance($this->cartInstanceName)->all()['items'] ?? [];
        $prices = Financial::calculateMultipleProfits($items, Auth::user()->mobile);
        $this->esajPrice = $prices['esaj_price'];
        $this->esajprofit = $prices['esaj_profit'];
        $this->buyerPrice = $prices['buyer_price'];
        $this->buyerProfit = $prices['buyer_profit'];

        $esajResponse = Financial::handleEsajProfit(
            $this->esajPrice,
            $this->esajprofit,
            $this->order
        );

        $buyerResponse = Financial::handleBuyerTransaction(
            $this->buyerPrice,
            $this->buyerProfit,
            $this->user,
            $this->order,
            $this->originalPrice,
            $this->takenValue ?? null,
        );

        if($buyerResponse && $esajResponse) {

            $product = Product::first();
            Financial::transactionsAfterTopUp(
                $this->user->id,
                $this->order->id,
                [],
                1,
                '',
                $product,
                $this->takenValue
            );

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
        $this->esajUser = User::where('mobile', User::MOBILE_ESAJ)->first();
        $this->originalPrice = $data['value'];
        $this->takenValue = $data['takenValue'] ?? $this->originalPrice;
        $this->webserviceCode = $data['webserviceCode'] ?? null;
        $this->cartInstanceName = $data['cartInstanceName'] ?? 'esaj';
    }
}
