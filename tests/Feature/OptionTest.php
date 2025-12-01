<?php

namespace Tests\Feature;

use App\Models\Option;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class OptionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private mixed $role;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user = User::factory()->create();
        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('option.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );
    }

    public function test_storing_and_deleting_option(): void
    {
        $this->user->assignRole($this->role);

        $response = $this->post('/api/options', [
            'name'        => fake()->word,
            'code'        => fake()->unique()->slug(2),
            'type'        => 'select',
            'is_required' => true,
            'is_active'   => true,
            'meta'        => [
                'description' => 'Sample option meta',
            ],
            'sort_order'  => 0,
        ]);

        $response->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $option = Option::first();

        // deleting
        $response = $this->delete('/api/options/' . $option->id);

        $response->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.deletedSuccessfully', ['id' => $option->id]),
        ]);
    }

    public function test_updating_option(): void
    {
        $this->user->assignRole($this->role);

        $option = Option::create([
            'name'        => fake()->word,
            'code'        => fake()->unique()->slug(2),
            'type'        => 'select',
            'is_required' => false,
            'is_active'   => true,
            'meta'        => ['description' => 'Old meta'],
            'sort_order'  => 0,
        ]);

        $response = $this->patch('/api/options/' . $option->id, [
            'name'        => 'Updated name',
            'is_required' => true,
            'meta'        => [
                'description' => 'Updated meta',
            ],
        ]);

        $response->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.updatedSuccessfully', ['id' => $option->id]),
        ]);
    }

    public function test_fetching_options(): void
    {
        $this->user->assignRole($this->role);

        Option::create([
            'name'        => fake()->word,
            'code'        => fake()->unique()->slug(2),
            'type'        => 'select',
            'is_required' => false,
            'is_active'   => true,
            'meta'        => ['description' => 'List meta'],
            'sort_order'  => 0,
        ]);

        // لیست کامل
        $response = $this->get('/api/options');

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        $firstModel = Option::first();

        // دریافت با id
        $response2 = $this->get('/api/options?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    public function test_fetching_clients_options(): void
    {
        // اینجا لازم نیست حتماً رول داشته باشیم، چون clientIndex معمولاً authorize نداره
        Option::create([
            'name'        => fake()->word,
            'code'        => fake()->unique()->slug(2),
            'type'        => 'select',
            'is_required' => false,
            'is_active'   => true,
            'meta'        => ['description' => 'Client meta'],
            'sort_order'  => 0,
        ]);

        $response = $this->get('/api/clients/options');

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        $firstModel = Option::where('is_active', 1)->first();

        $response2 = $this->get('/api/clients/options?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
