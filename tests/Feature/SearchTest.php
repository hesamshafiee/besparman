<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $user = User::factory()->create(['type' => User::TYPE_ADMIN]);
        $role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $role->givePermissionTo('user.*');
        $role->givePermissionTo('product.*');

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $user->assignRole($role);
    }

//    /**
//     * @return void
//     */
//    public function test_search(): void
//    {
//        $response = $this->post('/api/search', [
//            'table' => 'user',
//            'search' => '98912'
//        ]);
//
//        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
//            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
//        );
//    }

    /**
     * @return void
     */
    public function test_filter(): void
    {
        Product::factory()->create();

        $items['status'] = '0';

        $response = $this->post('/api/filter', [
            'table' => 'product',
            'items' => $items
        ]);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );
    }
}
