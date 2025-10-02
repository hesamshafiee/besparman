<?php

namespace Tests\Feature;

use App\Models\Logistic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class LogisticTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $user = User::factory()->create();
        $role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $role->givePermissionTo('logistic.*');

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $user->assignRole($role);
    }

    public function test_storing_and_deleting_logistic(): void
    {
        $response = $this->post('/api/logistics', [
            'city' => fake()->city(),
            'province' => fake()->city(),
            'country' => fake()->country(),
            'type' => 'a',
            'capacity' => 20,
            'price' => 3000,
            'start_time' => 8,
            'end_time' => 18,
            'divide_time' => 2,
            'is_active_in_holiday' => 1,
            'days_not_working' => '{"monday" : {}}',
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $logistic = Logistic::first();

        //deleting
        $response = $this->delete('/api/logistics/' . $logistic->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $logistic->id]),
        ]);
    }

    public function test_updating_logistic() : void
    {
        $logistic = Logistic::factory()->create();

        $response = $this->patch('/api/logistics/' . $logistic->id, [
            'city' => fake()->city(),
            'province' => fake()->city(),
            'country' => fake()->country(),
            'type' => 'a',
            'capacity' => 20,
            'price' => 3000,
            'start_time' => 8,
            'end_time' => 18,
            'divide_time' => 2,
            'is_active_in_holiday' => 1,
            'days_not_working' => '{"monday" : {}}',
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $logistic->id]),
        ]);
    }

    public function test_fetching_logistics() : void
    {
        Logistic::factory()->create();

        $response = $this->get('/api/logistics');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Logistic::first();

        $response2 = $this->get('/api/logistics?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_fetching_clients_logistics() : void
    {
        Logistic::factory()->create(['status' => 1]);

        $response = $this->get('/api/clients/logistics');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Logistic::where('status', 1)->first();

        $response2 = $this->get('/api/clients/logistics?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
