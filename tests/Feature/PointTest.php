<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Point;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class PointTest extends TestCase
{
    use RefreshDatabase;

    private int $operatorId;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $user = User::factory()->create();
        $role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $role->givePermissionTo('point.*');

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $user->assignRole($role);

        $operator = Operator::first();
        $this->operatorId = $operator->id;
    }

    public function test_storing_and_deleting_point(): void
    {
        $response = $this->post('/api/points', [
            'operator_id' => $this->operatorId,
            'value' => 1000,
            'point' => 1,
            'type' => Point::TYPE_CELL_DIRECT_CHARGE,
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $point = Point::first();

        //deleting
        $response = $this->delete('/api/points/' . $point->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $point->id]),
        ]);
    }

    public function test_updating_point() : void
    {
        $point = Point::factory()->create(['operator_id' => $this->operatorId]);

        $response = $this->patch('/api/points/' . $point->id, [
            'operator_id' => $this->operatorId,
            'value' => 10000,
            'point' => 2,
            'type' => Point::TYPE_CELL_DIRECT_CHARGE,
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $point->id]),
        ]);
    }

    public function test_fetching_points() : void
    {
        Point::factory()->create(['operator_id' => $this->operatorId]);

        $response = $this->get('/api/points');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Point::first();

        $response2 = $this->get('/api/points?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
