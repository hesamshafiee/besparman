<?php
namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Product;
use App\Models\PointHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;


class PointHistoryTest extends TestCase
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





    public function test_fetching_point_histories_admin(): void
    {
        $this->user->assignRole($this->role);

        $product = Product::first();

        $pointHistory = PointHistory::create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'type' => 'test',
            'product_name' => $product->name,
            'point' => 10,
        ]);

        $response = $this->get('/api/point-histories');

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );
    }

    public function test_fetching_point_histories_user(): void
    {
        $product = Product::first();

        $pointHistory = PointHistory::create([
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'type' => 'test',
            'product_name' => $product->name,
            'point' => 10,
        ]);

        $response = $this->get('/api/client/point-histories');

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );
    }
}
