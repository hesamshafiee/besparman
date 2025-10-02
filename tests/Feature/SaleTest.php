<?php

namespace Tests\Feature;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Support\Str;

class SaleTest extends TestCase
{
    use RefreshDatabase;


    private $user;
    private $permission;
    private $role;


    /**
     * @return void
     */
    public function setUp() :void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user = User::factory()->create();
        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('sale.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );
    }


    /**
     * @return void
     */
    public function test_storing_and_deleting_sale() : void
    {
        $this->user->assignRole($this->role);

        $response = $this->post('/api/sales', [
            'title' => fake()->title,
            'type' => Sale::TYPE_PERCENT,
            'value' => 50,
            'start_date' => '2022-05-20 22:05:00',
            'end_date' => '2023-05-20 22:05:00',
            'status' => Sale::STATUS_ACTIVE
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $sale = Sale::first();

        //deleting
        $response = $this->delete('/api/sales/' . $sale->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $sale->id]),
        ]);
    }


    /**
     * @return void
     */
    public function test_updating_sale() : void
    {
        $this->user->assignRole($this->role);

        $sale = Sale::create([
            'title' =>  fake()->title,
            'type' => Sale::TYPE_MONEY,
            'value' => 1000,
            'status' => Sale::STATUS_INACTIVE,
            'start_date' => '2020-05-20 22:05:00',
            'end_date' => '2023-05-20 22:05:00'
        ]);

        $response = $this->patch('/api/sales/' . $sale->id, ['value' => 700,'type' => Sale::TYPE_MONEY]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $sale->id]),
        ]);
    }


    /**
     * @return void
     */
    public function test_fetching_sales() : void
    {
        $this->user->assignRole($this->role);

        Sale::create([
            'title' =>  fake()->title,
            'type' => Sale::TYPE_MONEY,
            'value' => 1000,
            'status' => Sale::STATUS_INACTIVE,
            'start_date' => '2020-05-20 22:05:00',
            'end_date' => '2023-05-20 22:05:00'
        ]);

        $response = $this->get('/api/sales/');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Sale::first();

        $response2 = $this->get('/api/sales?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }

}
