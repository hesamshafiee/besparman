<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Prize;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class PrizeTest extends TestCase
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
        $role->givePermissionTo('prize.*');

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $user->assignRole($role);

        $operator = Operator::first();
        $this->operatorId = $operator->id;
    }

    public function test_storing_and_deleting_and_status_prize(): void
    {
        $response = $this->post('/api/prizes', [
            'name' => fake()->name,
            'operator_id' => $this->operatorId,
            'price' => 1000,
            'point' => 100,
            'type' => Prize::TYPE_PHYSICAL,
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $prize = Prize::first();

        //changing status
        $response = $this->patch('/api/prizes/status/' . $prize->id);

        $response->assertStatus(200);

        //deleting
        $response = $this->delete('/api/prizes/' . $prize->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $prize->id]),
        ]);
    }

    public function test_updating_prize() : void
    {
        $prize = Prize::factory()->create(['operator_id' => $this->operatorId]);

        $response = $this->patch('/api/prizes/' . $prize->id, [
            'name' => fake()->name(),
            'operator_id' => $this->operatorId,
            'price' => 1000,
            'point' => 100,
            'type' => Prize::TYPE_PHYSICAL,
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $prize->id]),
        ]);
    }

    public function test_fetching_prizes() : void
    {
        Prize::factory()->create(['operator_id' => $this->operatorId]);

        $response = $this->get('/api/prizes');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Prize::first();

        $response2 = $this->get('/api/prizes?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_fetching_clients_prizes() : void
    {
        Prize::factory()->create(['operator_id' => $this->operatorId]);

        $response = $this->get('/api/clients/prizes');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Prize::first();
        $firstModel->status = Prize::STATUS_ACTIVE;
        $firstModel->save();

        $response2 = $this->get('/api/clients/prizes?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }

//    public function test_fetching_purchase_prizes() : void
//    {
//        Prize::factory()->create(['operator_id' => $this->operatorId]);
//
//        $response = $this->get('/api/prizes-purchase');
//
//        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
//            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
//        );
//
//        $firstModel = Prize::first();
//
//        $response2 = $this->get('/api/prizes-purchase/'. $firstModel->id);
//
//        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
//        $json->hasAll(['data', 'balance', 'additional'])
//        );
//    }
//
//    /**
//     * @return void
//     */
//    public function test_fetching_purchase_clients_prizes() : void
//    {
//        Prize::factory()->create(['operator_id' => $this->operatorId]);
//
//        $response = $this->get('/api/clients/prizes-purchase');
//
//        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
//        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
//        );
//
//        $firstModel = Prize::first();
//        $firstModel->status = Prize::STATUS_ACTIVE;
//        $firstModel->save();
//
//        $response2 = $this->get('/api/clients/prizes-purchase/'. $firstModel->id);
//
//        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
//        $json->hasAll(['data', 'balance', 'additional'])
//        );
//    }
}
