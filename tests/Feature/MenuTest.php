<?php

namespace Tests\Feature;

use App\Models\Logistic;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private mixed $permission;
    private mixed $role;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user = User::factory()->create();
        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('menu.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );
    }

    public function test_storing_and_deleting_menu(): void
    {
        $this->user->assignRole($this->role);

        $response = $this->post('/api/menus', [
            'title' => fake()->title,
            'items' =>json_encode([
                'title' => fake()->name,
                'link' => '#' ,
            ])
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $menu = Menu::first();

        //deleting
        $response = $this->delete('/api/menus/' . $menu->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $menu->id]),
        ]);
    }

    public function test_updating_menu() : void
    {
        $this->user->assignRole($this->role);

        $menu = Menu::create([
            'title' => fake()->title,
            'items' =>json_encode([
                'title' => fake()->name,
                'link' => '#' ,
            ])
        ]);

        $response = $this->patch('/api/menus/' . $menu->id, ['items' =>json_encode([
            'title' => fake()->name,
            'link' => '#' ,
        ])]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $menu->id]),
        ]);
    }

    public function test_fetching_menus() : void
    {
        $this->user->assignRole($this->role);

        Menu::create([
            'title' => fake()->title,
            'items' =>json_encode([
                'title' => fake()->name,
                'link' => '#' ,
            ])
        ]);

        $response = $this->get('/api/menus/');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Menu::first();

        $response2 = $this->get('/api/menus?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_fetching_clients_menus() : void
    {
        Menu::factory()->create(['status' => 1]);

        $response = $this->get('/api/clients/menus');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Menu::where('status', 1)->first();

        $response2 = $this->get('/api/clients/menus?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
