<?php


namespace App\Services\V1\Financial;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array calculateProfit(Product $product, string $mobile, string $price, bool $mainPage);
 * @method static array calculateMultipleProfits(Product $product, string $mobile);
 * @method static array createOrder(string $status = Order::STATUSRESERVED, string $store = 'esaj', Product $product = null, string $mobile = '', string $price = null, string $detail = null, string $discountCode = null, bool $mainPage = false);
 * @method static array cancellingOrder(Order $order, string $message)
 * @method static bool handleEsajProfit(float $esajPrice, float $esajProfit, Order $order, Product|null $product = null, bool $mainPage = false, int|null $groupId = null, array|null $thirdPartyInfo = null, bool|null $thirdPartyStatus = null, string|null $)
 * @method static bool handleBuyerTransaction(float $buyerPrice, float $buyerProfit, User $user, Order $order, float $originalPrice, ?string $takenValue, bool $mainPage = false, ?Product $product = null, ?string $mobile = null, ?int $groupId = null, ?string $webserviceCode = null)
 * @method static bool calculateUserPoints(User $user, Product $product, Order $order)
 */
class Financial extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'financial';
    }
}
