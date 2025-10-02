<?php


use App\Models\Operator;
use App\Models\Product;
use App\Models\IrancellOfferPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;


class IrancellOfferPackageTest extends TestCase
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
        $this->role->givePermissionTo('point-history.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );

        $operator = Operator::where('name', 'IRANCELL')->first();;
        $this->operatorId = $operator->id;
    }





    public function test_fetching_Irancell_offer_package_admin(): void
    {
        $this->user->assignRole($this->role);



        $irancellOfferPackage = IrancellOfferPackage::create([
            'user_id' => $this->user->id,
            'mobile_number' => '989300000000',
            'offerCode' => 'offerCode',
            'name' => 'Test Offer',
            'amount' => 1000,
            'offerType' => 'offerType',
            'validityDays' => '30',
            'offerDesc' => 'offerDesc'
        ]);

        $response = $this->get('/api/point-histories');

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );
    }
}
