<?php

namespace Tests\Feature;

use App\Models\Logistic;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $secondUser;
    private $permission;
    private $role;

    const SUPER_ADMIN = 'super-admin';

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user = User::factory()->create(['type' => User::TYPE_ORIDINARY]);
        $this->secondUser = User::factory()->create(['type' => User::TYPE_PANEL]);
        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('payment.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );

        $this->user->assignRole($this->role);
    }

    /**
     * @return void
     */
    public function test_fetching_payments(): void
    {
        $response = $this->get('/api/payments');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_fetching_clients_payments() : void
    {
        $response = $this->get('/api/clients/payments');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $image = UploadedFile::fake()->image('photo.png');
        $this->post('/api/payment/card/increase', ['image' => [$image], 'value' => '2000000'])->assertStatus(200);

        $firstModel = Payment::first();

        $response2 = $this->get('/api/clients/payments?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_fetching_orders(): void
    {
        $response = $this->get('/api/orders');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_fetching_clients_orders() : void
    {
        $response = $this->get('/api/clients/orders');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_increase_by_card_confirm(): void
    {
        $image = UploadedFile::fake()->image('photo.png');
        $this->post('/api/payment/card/increase', ['image' => [$image], 'value' => '2000000'])->assertStatus(200);

        $payment = Payment::where('type', Payment::TYPE_CARD)->first();

        $this->get('/api/payment/confirm/' . $payment->id)->assertStatus(200);

        $payment = Payment::where('type', Payment::TYPE_CARD)->where('status', Payment::STATUSPAID)->first();
        self::assertTrue(!empty($payment));

        $walletValue = Wallet::where('user_id', $this->user->id)->first()->value;
        self::assertTrue($walletValue === '2000000.0000');

        $firstModel = Payment::first();

        $response2 = $this->get('/api/payments?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_increase_by_card_reject(): void
    {
        $image = UploadedFile::fake()->image('photo.png');
        $this->post('/api/payment/card/increase', ['image' => [$image], 'value' => '2000000'])->assertStatus(200);

        $payment = Payment::where('type', Payment::TYPE_CARD)->first();

        $this->get('/api/payment/reject/' . $payment->id)->assertStatus(200);

        $payment = Payment::where('type', Payment::TYPE_CARD)->where('status', Payment::STATUSREJECT)->first();
        self::assertTrue(!empty($payment));
    }
}
