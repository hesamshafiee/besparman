<?php

namespace App\Services\V1\Wallet;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\WalletTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection transfer(string $value, int $transferToId);
 * @method static Collection rejectTransfer(int $transactionId, string $message);
 * @method static Collection confirmTransfer(int $transactionId);
 * @method static Collection cardToCard(Payment $payment);
 * @method static Collection increaseByAdmin(string $value, int $userId, string $message);
 * @method static Collection increaseByBank(Payment $payment);
 * @method static Collection decreaseByBank(Payment $payment);
 * @method static Collection increaseByRefund(WalletTransaction $transaction);
 * @method static Collection decreaseByAdmin(string $value, int $userId, string $message);
 * @method static Collection pay(string $returnUrl, string $bank);
 * @method static Collection payCardCharge(string $takenValue, string $webserviceCode, string $cartInstanceName);
 * @method static Collection payWithoutCart(Product $product, string $mobile, string $price, string $offerCode, string $offerType,  string $takenValue, string $type, int $ext_id, $webserviceCode, bool $fakeResponse, string $returnUrl, string $userMobile, bool $mainPage, string|null $discountCode, int|null $group_id, string|null $multipleTopupId);
 * @method static Collection continueAfterBank(Order $order, bool $topup = false);
 */
class Wallet extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'wallet';
    }
}
