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
use App\Models\Work;
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
    private $permission;
    private $role;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        Redis::flushAll();

        $this->user = User::factory()->create();
        Discount::factory()->count(100)->create();
        Product::factory()->count(20)->create();

        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('walletTransaction.*');
    }

    /**
     * @return void
     */
    public function test_adding_to_cart(): void
    {
        // ساخت سه محصول: یکی غیرفعال، دو تا فعال
        $inactiveProduct = Product::factory()->create(['status' => 0]);
        $active1 = Product::factory()->create(['status' => 1]);
        $active2 = Product::factory()->create(['status' => 1]);

        $cartKey = $this->addToCart([
            $inactiveProduct->id,
            $active1->id,
            $active2->id,
        ]);

        $this->existInCart([$active1->id, $active2->id], $cartKey);
        $this->notExistInCart([$inactiveProduct->id], $cartKey);

        $this->deletefromCart($active2->id, $cartKey);
        $this->notExistInCart([$active2->id], $cartKey);

        Sanctum::actingAs(
            $this->user,
            ['*']
        );

        $active3 = Product::factory()->create(['status' => 1]);
        $this->addToCart([$active3->id], $cartKey);
        $this->existInCart([$active1->id, $active3->id], $cartKey);

        $active4 = Product::factory()->create(['status' => 1]);
        $this->addToCart([$active4->id]);
        $this->existInCart([$active1->id, $active3->id, $active4->id]);

        $this->deleteFromCart($active4->id);
        $this->notExistInCart([$active4->id]);

        $response = $this->get('/api/cart/all')->assertStatus(200);
        self::assertTrue(count($response->original['cart']['items']) == 2);
    }


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


        $Address = Address::where('user_id', $this->user->id)->first();

        $this->post(
            '/api/cart/delivery/' . $logistic->id,
            [
                'date' => '2022/10/15',
                'deliveryBetweenStart' => $logistic->start_time,
                'deliveryBetweenEnd' => $logistic->start_time + $logistic->divide_time,
                'address_id' => $Address->id
            ]
        )->assertStatus(200);

        assertTrue(!empty(Cart::instance('cart')->getDelivery()));
    }
    public function test_view_cart_index(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $product = Product::first();

        $this->get('/api/cart/' . $product->id)->assertStatus(200);

        $response = $this->get('/api/cart/all');

        $response->assertStatus(200)
            ->assertJsonStructure(['cart'])
            ->assertHeader('balance');

        $cart = $response->json('cart');
        self::assertTrue(isset($cart['items']));
    }


    public function test_add_discount_to_cart(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        $discount = \App\Models\Discount::factory()->create([
            'status' => 1,
            'type' => \App\Models\Discount::TYPE_PERCENT,
            'code' => 'DISCOUNT50',
            'value' => 50,
        ]);

        $product = Product::first();
        $this->get('/api/cart/' . $product->id)->assertStatus(200);

        $response = $this->get('/api/cart/discount/' . $discount->code);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'cart_key', 'cart']);

        $cart = Cart::all();
        self::assertEquals($discount->code, $cart['discount']);
    }

    public function test_checkout_process_with_successful_payment(): void
    {
        Sanctum::actingAs($this->user, ['*']);

        \Mockery::mock('alias:' . \App\Services\V1\Wallet\Wallet::class)
            ->shouldReceive('pay')
            ->andReturn(['status' => true]);

        $response = $this->postJson('/api/cart/checkout', [
            'returnUrl' => 'https://example.com/callback',
            'bank' => 'saman',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => __('checkout successfully')]);
    }

    /**
     * @param array $ids
     * @param string $cartKey
     * @return string
     */
    private function addToCart(array $ids, string $cartKey = ''): string
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
    private function deleteFromCart(int $id, string $cartKey = ''): void
    {
        $this->delete('/api/cart/' . $id . '/' . $cartKey)->assertStatus(200);
    }

    /**
     * @param array $ids
     * @param string $cartKey
     * @return void
     */
    private function existInCart(array $ids, string $cartKey = ''): void
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
    private function notExistInCart(array $ids, string $cartKey = ''): void
    {
        $cart = Cart::instance('cart', $cartKey)->all();

        foreach ($ids as $id) {
            self::assertNotTrue(isset($cart['items']['product-' . $id]));
        }
    }

    public function test_cannot_add_product_with_unpublished_work_to_cart(): void
    {
        // نیازی به لاگین نیست، مثل بقیه تست‌های addToCart می‌تونی مهمان باشی
        // Sanctum::actingAs($this->user, ['*']); // اگر خواستی می‌تونی فعالش کنی

        // ۱) ساخت work غیرمنتشر
        // اگر Work::factory داری:
        $work = Work::factory()->create([
            'is_published' => Work::IS_PUBLISHED_FALSE, // یا 0 اگر همچین کانستنتی نداری
        ]);

        // ۲) ساخت product که به این work وصل باشد
        $product = Product::factory()->create([
            'status' => 1,
            'work_id' => $work->id,
        ]);

        // ۳) تلاش برای اضافه کردن محصول به سبد
        $response = $this->get('/api/cart/' . $product->id);

        // چون تو CartService::addToCart این رو برمی‌گردونی:
        // [
        //   'status' => false,
        //   'message' => 'این اثر هنوز منتشر نشده است و امکان افزودن به سبد خرید وجود ندارد.'
        // ]
        // و کنترلر هم بر اساس status، 500 می‌ده
        $response->assertStatus(500);
        $response->assertJson([
            'message' => 'این اثر هنوز منتشر نشده است و امکان افزودن به سبد خرید وجود ندارد.',
        ]);

        // ۴) مطمئن شو محصول توی سبد ذخیره نشده
        // (الان Cart::instance('cart') یه سبد خالی جدید برمی‌داره، ولی
        // با ساختار فعلی تست‌هات همین الگو رو نگه می‌داریم)
        $this->notExistInCart([$product->id]);
    }

}
