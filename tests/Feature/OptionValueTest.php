<?php

namespace Tests\Feature;

use App\Models\Option;
use App\Models\OptionValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class OptionValueTest extends TestCase
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
        $this->role->givePermissionTo('option-value.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );
    }

    public function test_storing_and_deleting_option_value(): void
    {
        $this->user->assignRole($this->role);

        $option = Option::create([
            'name'        => fake()->word,
            'code'        => fake()->unique()->slug(2),
            'type'        => 'select',
            'is_required' => false,
            'is_active'   => true,
            'meta'        => [],
            'sort_order'  => 0,
        ]);

        $response = $this->post('/api/options/values/' . $option->id , [
            'name'      => fake()->word,
            'code'      => fake()->unique()->slug(2),
            'is_active' => true,
            'meta'      => [
                'description' => 'Sample value meta',
                'price_modifier' => 10000,
            ],
            'sort_order' => 0,
        ]);

        $response->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $value = OptionValue::where('option_id', $option->id)->first();

        // deleting
        $response = $this->delete('/api/options/values/' . $option->id . '/' . $value->id);

        $response->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.deletedSuccessfully', ['id' => $value->id]),
        ]);
    }

    public function test_updating_option_value(): void
    {
        $this->user->assignRole($this->role);

        $option = Option::create([
            'name'        => fake()->word,
            'code'        => fake()->unique()->slug(2),
            'type'        => 'select',
            'is_required' => false,
            'is_active'   => true,
            'meta'        => [],
            'sort_order'  => 0,
        ]);

        $value = OptionValue::create([
            'option_id'  => $option->id,
            'name'       => fake()->word,
            'code'       => fake()->unique()->slug(2),
            'is_active'  => true,
            'meta'       => ['description' => 'Old meta'],
            'sort_order' => 0,
        ]);

        $response = $this->patch('/api/options/values/' . $option->id . '/' . $value->id, [
            'name'      => 'Updated value name',
            'is_active' => false,
            'meta'      => [
                'description' => 'Updated meta',
                'price_modifier' => 5000,
            ],
        ]);

        $response->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.updatedSuccessfully', ['id' => $value->id]),
        ]);
    }

    public function test_fetching_option_values(): void
    {
        $this->user->assignRole($this->role);

        $option = Option::create([
            'name'        => fake()->word,
            'code'        => fake()->unique()->slug(2),
            'type'        => 'select',
            'is_required' => false,
            'is_active'   => true,
            'meta'        => [],
            'sort_order'  => 0,
        ]);

        OptionValue::create([
            'option_id'  => $option->id,
            'name'       => fake()->word,
            'code'       => fake()->unique()->slug(2),
            'is_active'  => true,
            'meta'       => ['description' => 'List meta'],
            'sort_order' => 0,
        ]);

        $response = $this->get('/api/options/values/' . $option->id . '');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        $firstModel = OptionValue::where('option_id', $option->id)->first();

        $response2 = $this->get('/api/options/values/' . $option->id . '?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    public function test_fetching_clients_option_values(): void
    {
        $option = Option::create([
            'name'        => fake()->word,
            'code'        => fake()->unique()->slug(2),
            'type'        => 'select',
            'is_required' => false,
            'is_active'   => true,
            'meta'        => [],
            'sort_order'  => 0,
        ]);

        OptionValue::create([
            'option_id'  => $option->id,
            'name'       => fake()->word,
            'code'       => fake()->unique()->slug(2),
            'is_active'  => 1,
            'meta'       => ['description' => 'Client meta'],
            'sort_order' => 0,
        ]);

        $response = $this->get('/api/clients/options/values/' . $option->id . '');
        

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        $firstModel = OptionValue::where('option_id', $option->id)
            ->where('is_active', 1)
            ->first();

        $response2 = $this->get('/api/clients/options/values/' . $option->id . '?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
