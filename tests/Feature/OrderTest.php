<?php
namespace Tests\Feature;

use App\Models\Logistic;
use App\Models\Operator;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;


class OrderTest extends TestCase
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
        $this->role->givePermissionTo('logistic.*');
        $this->role->givePermissionTo('walletTransaction.*');
        $this->role->givePermissionTo('order.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );

        $operator =  Operator::first();
        $this->operatorId = $operator->id;
    }


    public function test_fetching_orders(): void
    {
        $this->user->assignRole($this->role);
        $response = $this->get('/api/orders/physical');

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );
    }

}
