<?php

namespace Tests\Feature;

use App\Models\Discount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DiscountTest extends TestCase
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
        $this->role->givePermissionTo('discount.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );
    }

    /**
     * @return void
     */
    public function test_storing_and_deleting_discount() : void
    {
        $this->user->assignRole($this->role);

        $response = $this->post('/api/discounts', ['code' =>  Str::random(50), 'type' => Discount::TYPE_PERCENT, 'value' => '50.5']);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $discount = Discount::first();

        //deleting
        $response = $this->delete('/api/discounts/' . $discount->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $discount->id]),
        ]);
    }

    /**
     * @return void
     */
    public function test_updating_discount() : void
    {
        $this->user->assignRole($this->role);
        $discount = Discount::create(['code' =>  Str::random(50), 'type' => Discount::TYPE_PERCENT, 'value' => '50.5']);

        $response = $this->patch('/api/discounts/' . $discount->id, ['value' => 70, 'reusable' => 1]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $discount->id]),
        ]);
    }

    /**
     * @return void
     */
    public function test_fetching_discounts() : void
    {
        $this->user->assignRole($this->role);

        Discount::create(['code' =>  Str::random(50), 'type' => Discount::TYPE_PERCENT, 'value' => '50.5']);

        $response = $this->get('/api/discounts/');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Discount::first();

        $response2 = $this->get('/api/discounts?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
