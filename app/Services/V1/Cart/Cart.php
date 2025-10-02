<?php


namespace App\Services\V1\Cart;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use phpDocumentor\Reflection\Types\Boolean;

/**
 * Class Cart
 * we are creating facade for our service here
 * @package App\Helpers\Cart
 * @method static Collection all();
 * @method static Collection addToCart(mixed $product, int $quantity = 1);
 * @method static Collection delete(mixed $product);
 * @method static Cart instance(string $name, string $cartKey = null);
 * @method static void dbGet(string $name);
 * @method static void flush();
 */
class Cart extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cart';
    }
}
