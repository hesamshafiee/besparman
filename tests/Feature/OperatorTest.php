<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class OperatorTest extends TestCase
{
    use RefreshDatabase;

    private int $operatorId;
    private Operator $operator;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $user = User::factory()->create();
        $role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $role->givePermissionTo('operator.*');

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $user->assignRole($role);

        $this->operator = Operator::first();
    }

    public function test_updating_operator() : void
    {
        $response = $this->patch('/api/operators/' . $this->operator->id, [
            'title' => 'mci',
            'status' => 1,
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $this->operator->id]),
        ]);
    }

    public function test_fetching_operators() : void
    {
        $response = $this->get('/api/operators');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Operator::first();

        $response2 = $this->get('/api/operators?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
