<?php


use App\Models\Operator;
use App\Models\CardCharge;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;


class CardChargeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private $secondUser;
    private mixed $permission;
    private mixed $role;
    private int $operatorId;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user = User::factory()->create(['type' => 'panel', 'profile_confirm' => now()]);
        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('card-charge.*');
        $this->role->givePermissionTo('walletTransaction.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );

        $operator = Operator::where('name', 'IRANCELL')->first(); ;
        $this->operatorId = $operator->id;
    }


    public function test_buy_card_charge(): void
    {
        $this->user->assignRole($this->role);
        $response = $this->post('/api/wallet/increase-by-admin', ['userId' => $this->user->id, 'value' => '100000000',
            'message' => 'test test test test test test']);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('increased successfully'),
        ]);

        $response = $this->post('/api/card-charge/', [
            'file_name' => 'Irancelltest',
            'card_charges' => '[{"pin":"rferter", "serial": "1","price": 200000},
                {"pin":"2", "serial": "2","price": 200000}]',
            'operator_id' => $this->operatorId,
        ]);


        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);


        $product = Product::where([
            'price' => 200000,
            'operator_id' => $this->operatorId,
            'type' => Product::TYPE_CARD_CHARGE
        ])->first();

        $payload = [
            'taken_value' => '400000',
            'products' => json_encode([
                ['product_id' => $product->id, 'count' => 2]
            ]),
        ];

        $response = $this->postJson('/api/clients/card-charge/buy', $payload);


        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['status', 'message', 'data'])
                ->where('status', true)
                ->where('message', 'Card charges purchased successfully')
            );
    }

    public function test_storing_card_charge(): void
    {

        $this->user->assignRole($this->role);

        $response = $this->post('/api/card-charge/', [
            'file_name' => 'Irancelltest',
            'card_charges' => '[{"pin":"rferter", "serial": "1","price": 200000},
                {"pin":"2", "serial": "2","price": 200000}]',
            'operator_id' => $this->operatorId,
        ]);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

    }



    public function test_storing_card_charge_and_destroy_open_card_charges(): void
    {

        $this->user->assignRole($this->role);

        $response = $this->post('/api/card-charge/', [
            'file_name' => 'Irancelltest',
            'card_charges' => '[{"pin":"rferter", "serial": "1","price": 200000},
                {"pin":"2", "serial": "2","price": 200000}]',
            'operator_id' => $this->operatorId,
        ]);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);


        $cardCharge = CardCharge::first();

        $response = $this->get('/api/card-charge/destroyOpen/' . $cardCharge->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully'),
        ]);

    }


    public function test_fetching_card_charges(): void
    {

        $this->user->assignRole($this->role);

        $response = $this->post('/api/card-charge/', [
            'file_name' => 'Irancelltest',
            'card_charges' => '[{"pin":"rferter", "serial": "1","price": 200000},
                {"pin":"2", "serial": "2","price": 200000}]',
            'operator_id' => $this->operatorId,
        ]);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);



        $cardCharge = CardCharge::first();

        $response = $this->get('/api/card-charge' );

        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('data')
                ->has('balance')
                ->has('additional')
                ->has('links')
                ->has('meta')
            );

    }


    public function test_free_report_card_charges(): void
    {

        $this->user->assignRole($this->role);

        $response = $this->post('/api/card-charge/', [
            'file_name' => 'Irancelltest',
            'card_charges' => '[{"pin":"rferter", "serial": "1","price": 200000},
                {"pin":"2", "serial": "2","price": 200000}]',
            'operator_id' => $this->operatorId,
        ]);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);



        $cardCharge = CardCharge::first();

        $response = $this->get('/api/card-charge' );

        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('data')
                ->has('balance')
                ->has('additional')
                ->has('links')
                ->has('meta')
            );

    }


    public function test_find_by_serial_card_charges(): void
    {

        $this->user->assignRole($this->role);

        $response = $this->post('/api/card-charge/', [
            'file_name' => 'Irancelltest',
            'card_charges' => '[{"pin":"rferter", "serial": "1","price": 200000},
                {"pin":"2", "serial": "2","price": 200000}]',
            'operator_id' => $this->operatorId,
        ]);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);



        $cardCharge = CardCharge::first();

        $response = $this->get('/api/card-charge/findBySerial?serial='.$cardCharge->serial);


        $response->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('data')
                ->has('balance')
                ->has('additional')
            );

    }

}
