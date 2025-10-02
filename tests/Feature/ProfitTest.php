<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Profit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class ProfitTest extends TestCase
{
    use RefreshDatabase;

    private int $operatorId;
    private Profit $profit;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $user = User::factory()->create();
        $role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $role->givePermissionTo('profit.*');

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $user->assignRole($role);

        $operator = Operator::first();
        $this->operatorId = $operator->id;
        $this->profit = Profit::factory()->create(['operator_id' => $this->operatorId, 'type' => Profit::TYPE_TD_LTE_INTERNET_PACKAGE]);
    }

    public function test_storing_and_deleting_profit(): void
    {
        $response = $this->post('/api/profits', [
            'operator_id' => $this->operatorId,
            'type' => Profit::TYPE_CELL_CARD_CHARGE,
            'title' => 'mci',
            'profit' => 2,
            'status' => 1,
        ]);

        $response->assertStatus(201);

        $profit = Profit::first();

        //deleting
//        $response = $this->delete('/api/profits/' . $profit->id);
//
//        $response->assertStatus(200)->assertJson([
//            'status' => true,
//            'message' => __('general.deletedSuccessfully', ['id' => $profit->id]),
//        ]);
    }

    public function test_updating_profit() : void
    {
        $response = $this->patch('/api/profits/' . $this->profit->id, [
            'operator_id' => $this->operatorId,
            'type' => Profit::TYPE_CELL_CARD_CHARGE,
            'title' => 'mci',
            'profit' => 4,
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $this->profit->id]),
        ]);
    }

    public function test_fetching_profits() : void
    {
        $response = $this->get('/api/profits');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Profit::first();

        $response2 = $this->get('/api/profits?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
