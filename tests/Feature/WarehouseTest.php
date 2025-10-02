<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class WarehouseTest extends TestCase
{
    use RefreshDatabase;

    private int $productId;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $user = User::factory()->create();
        $role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $role->givePermissionTo('warehouse.*');

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $user->assignRole($role);

        $this->productId = Product::factory()->create()->id;
    }

    public function test_storing_and_deleting_warehouse(): void
    {
        $response = $this->post('/api/warehouses', [
            'product_id' => $this->productId,
            'count' => 100,
            'price' => 4000,
            'expiry_date' => fake()->date,
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $warehouse = Warehouse::first();

        //deleting
        $response = $this->delete('/api/warehouses/' . $warehouse->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $warehouse->id]),
        ]);
    }

    public function test_updating_warehouse() : void
    {
        $warehouse = Warehouse::factory()->create(['product_id' => $this->productId]);

        $response = $this->patch('/api/warehouses/' . $warehouse->id, [
            'product_id' => $this->productId,
            'count' => 100,
            'price' => 3000,
            'expiry_date' => fake()->date,
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $warehouse->id]),
        ]);
    }

    public function test_fetching_warehouses() : void
    {
        Warehouse::factory()->create(['product_id' => $this->productId]);

        $response = $this->get('/api/warehouses');
        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Warehouse::first();

        $response2 = $this->get('/api/warehouses?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
