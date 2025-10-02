<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Discount;
use App\Models\Logistic;
use App\Models\Order;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Warehouse;
use App\Services\V1\Cart\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use function PHPUnit\Framework\assertTrue;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    /**
     * @return void
     */
    public function setUp() :void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        Redis::flushAll();

        $this->user = User::factory()->create();
        Product::where('id', '<=', 10)->update(['status' => 0]);
        Product::where('id', '<=', 20)->update(['type' => Product::TYPE_CART]);
        Discount::factory()->count(100)->create();

        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('walletTransaction.*');
    }

    /**
     * @return void
     */
    public function test_adding_to_cart() : void
    {
        $cartKey = $this->addToCart([1, 12, 17]);
        $this->existInCart([12, 17], $cartKey);
        $this->notExistInCart([1], $cartKey);
        $this->deletefromCart(17, $cartKey);
        $this->notExistInCart([17], $cartKey);

        Sanctum::actingAs(
            $this->user,
            ['*']
        );

        $this->addToCart([14], $cartKey);
        $this->existInCart([12, 14], $cartKey);

        $this->addToCart([15]);
        $this->existInCart([12, 14, 15]);

        $this->deleteFromCart(15);
        $this->notExistInCart([15]);

        $response = $this->get('/api/cart/all')->assertStatus(200);
        self::assertTrue(count($response->original['cart']['items']) == 2);
    }

    /**
     * @return void
     */
//    public function test_discount() : void
//    {
//        Sanctum::actingAs(
//            $this->user,
//            ['*']
//        );
//
//        $discount = Discount::where('status', 1)->where('type', Discount::TYPE_PERCENT)->first();
//
//
//        $this->addToCart([35]);
//        $this->existInCart([35]);
//
//        $cart = Cart::all();
//        $beforeDiscount = $cart['items']['product-35']['discount_value'];
//
//        $this->get('/api/cart/discount/test')->assertStatus(500);
//
//
//        $this->get('/api/cart/discount/' . $discount->code)->assertStatus(200);
//
//        $cart = Cart::all();
//        self::assertTrue($cart['discount'] === $discount->code);
//
//        $afterDiscount = $cart['items']['product-35']['discount_value'];
//
//        self::assertNotTrue($beforeDiscount, $afterDiscount);
//        self::assertTrue($afterDiscount === $discount->value/100);
//
//
//        //sale
//        $cart = Cart::all();
//        $beforeSale = $cart['items']['product-35']['discount_value'];
//        Sale::factory()->count(1)->create(['status' => 1]);
//        $sale = Sale::where('status', Sale::STATUS_ACTIVE)->where('start_date', '<=', now())->where('end_date', '>=', now())->first();
//        if ($sale) {
//            $cart = Cart::all();
//            self::assertTrue($cart['discount'] === $discount->code);
//            $afterSale = $cart['items']['product-35']['sale_value'];
//            self::assertNotTrue($beforeSale, $afterSale);
//            self::assertTrue($afterSale === $sale->value/100);
//        }
//
//        $this->get('/api/cart/discount')->assertStatus(200);
//
//        $cart = Cart::all();
//        self::assertTrue(is_null($cart['discount']));
//    }

    /**
     * @return void
     */
//    public function test_checkout(): void
//    {
//        Sanctum::actingAs(
//            $this->user,
//            ['*']
//        );
//
//        $this->user->assignRole($this->role);
//
//
//        $allProducts = Product::where('status', 1)->get();
//
//        $product = $allProducts[0];
//        $product2 = $allProducts[1];
//        $product3 = $allProducts[2];
//
//        Warehouse::factory()->create(['product_id' => $product->id, 'count' => 2]);
//        Warehouse::factory()->create(['product_id' => $product3->id, 'count' => 0]);
//
//        $this->addToCart([$product->id, $product->id, $product2->id, $product3->id]);
//        $this->existInCart([$product->id, $product2->id]);
//        $this->notExistInCart([$product3->id]);
//
//        $this->post('/api/wallet/increase-by-admin', ['userId' => $this->user->id, 'value' => ($product->price * 2) + $product2->price,
//            'message' => 'test test test test test test'])->assertStatus(200);
//
//        $this->get('/api/cart/checkout')->assertStatus(200);
//
//        $walletValue = Wallet::where('user_id', $this->user->id)->first()->value;
//        self::assertTrue($walletValue === '0.0000');
//
//        $order = Order::first();
//        $orderables = $order->products->pluck('id')->toArray();
//        self::assertTrue(([$product->id, $product2->id] === $orderables));
//
//        $warehouse = Warehouse::where('product_id', $product->id)->first();
//        assertTrue($warehouse->count === 0);
//    }

    /**
     * @return void
     */
    public function test_adding_delivery(): void
    {
        Sanctum::actingAs(
            $this->user,
            ['*']
        );

        $product = Product::where('status', 1)->first();
        $this->addToCart([$product->id]);
        $this->existInCart([$product->id]);

        $logistic = Logistic::factory()->create();


        $addressData = [
            'title' => 'Home Address', // Using 'title' from model
            'province' => 'Tehran', // Using 'province' from model
            'city' => 'Tehran', // Using 'city' from model
            'address' => '123 Main St, Apt 4B', // Using 'address' from model
            'postal_code' => '12345-6789', // Using 'postal_code' from model
            'phone' => '02112345678', // Using 'phone' from model
            'mobile' => '09121234567', // Using 'mobile' from model
            'is_default' => true, // Using 'is_default' from model (boolean)
        ];

        $response = $this->postJson('/api/clients/addresses', $addressData);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);


        $Address = Address::first();

        $this->post('/api/cart/delivery/' . $logistic->id,
            [
                'date' => '2022/10/15',
                'deliveryBetweenStart' => $logistic->start_time,
                'deliveryBetweenEnd' => $logistic->start_time + $logistic->divide_time,
                'address_id' => $Address->id
            ]
        )->assertStatus(200);

        assertTrue(!empty(Cart::instance('cart')->getDelivery()));
    }

    /**
     * @param array $ids
     * @param string $cartKey
     * @return string
     */
    private function addToCart(array $ids, string $cartKey = '') : string
    {
        foreach ($ids as $id) {
            $response = $this->get('/api/cart/' . $id . '/' . $cartKey);
            $cartKey = $response->original['cart_key'];
        }
        return $cartKey;
    }

    /**
     * @param int $id
     * @param string $cartKey
     * @return void
     */
    private function deleteFromCart(int $id, string $cartKey = '') : void
    {
        $this->delete('/api/cart/' . $id . '/'. $cartKey)->assertStatus(200);
    }

    /**
     * @param array $ids
     * @param string $cartKey
     * @return void
     */
    private function existInCart(array $ids, string $cartKey = '') : void
    {
        $cart = Cart::instance('cart', $cartKey)->all();

        foreach ($ids as $id) {
            self::assertTrue(isset($cart['items']['product-' . $id]));
        }
    }

    /**
     * @param array $ids
     * @param string $cartKey
     * @return void
     */
    private function notExistInCart(array $ids, string $cartKey = '') : void
    {
        $cart = Cart::instance('cart', $cartKey)->all();

        foreach ($ids as $id) {
            self::assertNotTrue(isset($cart['items']['product-' . $id]));
        }
    }
}
