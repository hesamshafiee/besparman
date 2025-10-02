<?php

namespace Tests\Feature;

use App\Models\GroupCharge;
use App\Models\Operator;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;


class GroupChargeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private mixed $permission;
    private mixed $role;
    private int $productId;
    private int $ProductMinPrice;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user =  User::factory()->create();

        Sanctum::actingAs(
            $this->user,
            ['*']
        );
        $product = Product::factory()->create() ;
        $this->productId = $product->id;
        $this->ProductMinPrice = $product->price;
    }

    public function test_storing_topup_and_cancelling_group_charges(): void
    {
        $response = $this->post('/api/group-charge/topup/'.$this->productId, [
            'price' => $this->ProductMinPrice,
            'phone_numbers' => '{"phones": ["0912000000"]}',
            'refCode' => 'refCode',
            'operator_type' => 'type',
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $groupCharge = GroupCharge::first();

        //cancel GroupCharge
        $response = $this->post('/api/group-charge/cancel/' . $groupCharge->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);
    }

    public function test_storing_topup_and_update_force_group_charges(): void
    {
        $response = $this->post('/api/group-charge/topup/'.$this->productId, [
            'price' => $this->ProductMinPrice,
            'phone_numbers' => '{"phones": ["0912000000"]}',
            'refCode' => 'refCode',
            'operator_type' => 'type',
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $groupCharge = GroupCharge::first();

        //cancel GroupCharge
        $response = $this->post('/api/group-charge/force/' . $groupCharge->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);
    }
    public function test_storing_topup_package_and_cancelling_group_charges(): void
    {
        $response = $this->post('/api/group-charge/topup-package/'.$this->productId, [
            'price' => $this->ProductMinPrice,
            'phone_numbers' => '{"phones": ["0912000000"]}',
            'refCode' => 'refCode',
            'offerCode' => 'offerCode',
            'offerType' => 'offerType',
            'operator_type' => 'test',
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $groupCharge = GroupCharge::first();

        //cancel GroupCharge
        $response = $this->post('/api/group-charge/cancel/' . $groupCharge->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);
    }

    public function test_fetching_group_charges() : void
    {
        $response = $this->post('/api/group-charge/topup/'.$this->productId, [
            'price' => $this->ProductMinPrice,
            'phone_numbers' => '{"phones": ["0912000000"]}',
            'refCode' => 'refCode',
            'operator_type' => 'ggg',
        ]);

        $response = $this->get('/api/clients/group-charge');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = GroupCharge::first();

        $response2 = $this->get('/api/clients/group-charge?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    public function test_fetching_group_charges_admin() : void
    {

        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('group-charge.*');
        $this->user->assignRole($this->role);

        $response = $this->post('/api/group-charge/topup/'.$this->productId, [
            'price' => $this->ProductMinPrice,
            'phone_numbers' => '{"phones": ["0912000000"]}',
            'refCode' => 'refCode',
            'operator_type' => 'ggg',
        ]);


        $response = $this->get('/api/group-charge');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = GroupCharge::first();

        $response2 = $this->get('/api/group-charge?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
