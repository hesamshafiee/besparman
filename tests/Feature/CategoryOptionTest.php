<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryOption;
use App\Models\Option;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategoryOptionTest extends TestCase
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

        $this->role->givePermissionTo('category-option.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );
    }

    public function test_syncing_category_options(): void
    {
        $this->user->assignRole($this->role);

        $category = Category::factory()->create();

        $option1 = Option::create([
            'name'        => 'Color',
            'code'        => 'color-' . fake()->unique()->slug(1),
            'type'        => 'select',
            'is_required' => true,
            'is_active'   => true,
            'meta'        => [],
            'sort_order'  => 0,
        ]);

        $option2 = Option::create([
            'name'        => 'Size',
            'code'        => 'size-' . fake()->unique()->slug(1),
            'type'        => 'select',
            'is_required' => false,
            'is_active'   => true,
            'meta'        => [],
            'sort_order'  => 1,
        ]);

        $response = $this->post('/api/categories/options/sync/' . $category->id, [
            'options' => [
                [
                    'option_id'   => $option1->id,
                    'is_required' => true,
                    'is_active'   => true,
                    'sort_order'  => 0,
                ],
                [
                    'option_id'   => $option2->id,
                    'is_required' => false,
                    'is_active'   => true,
                    'sort_order'  => 1,
                ],
            ],
        ]);

        $response->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.updatedSuccessfully', ['id' => $category->id]),
        ]);

        $this->assertDatabaseHas('category_option', [
            'category_id' => $category->id,
            'option_id'   => $option1->id,
            'is_required' => true,
            'is_active'   => true,
            'sort_order'  => 0,
        ]);

        $this->assertDatabaseHas('category_option', [
            'category_id' => $category->id,
            'option_id'   => $option2->id,
            'is_required' => false,
            'is_active'   => true,
            'sort_order'  => 1,
        ]);
    }

    public function test_fetching_category_options(): void
    {
        $this->user->assignRole($this->role);

        $category = Category::factory()->create();

        $option = Option::create([
            'name'        => 'Material',
            'code'        => 'material-' . fake()->unique()->slug(1),
            'type'        => 'select',
            'is_required' => false,
            'is_active'   => true,
            'meta'        => [],
            'sort_order'  => 0,
        ]);

        $category->options()->attach($option->id, [
            'is_required' => true,
            'is_active'   => true,
            'sort_order'  => 0,
        ]);

        $response = $this->get('/api/categories/options/' . $category->id);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    public function test_fetching_clients_category_options(): void
    {
        // این متد authorize دستی ندارد، فقط لاگین بودن کافی است (Sanctum در setUp)
        $category = Category::factory()->create();

        $option = Option::create([
            'name'        => 'Style',
            'code'        => 'style-' . fake()->unique()->slug(1),
            'type'        => 'select',
            'is_required' => false,
            'is_active'   => true,
            'meta'        => [],
            'sort_order'  => 0,
        ]);

        $category->options()->attach($option->id, [
            'is_required' => null, // از option ارث می‌برد
            'is_active'   => true,
            'sort_order'  => 0,
        ]);

        $response = $this->get('/api/clients/categories/options/' . $category->id);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    public function test_detaching_category_option(): void
    {
        $this->user->assignRole($this->role);

        $category = Category::factory()->create();

        $option = Option::create([
            'name'        => 'Pattern',
            'code'        => 'pattern-' . fake()->unique()->slug(1),
            'type'        => 'select',
            'is_required' => false,
            'is_active'   => true,
            'meta'        => [],
            'sort_order'  => 0,
        ]);

        $category->options()->attach($option->id, [
            'is_required' => false,
            'is_active'   => true,
            'sort_order'  => 0,
        ]);

        $response = $this->delete('/api/categories/options/' . $category->id . '/' . $option->id);

        $response->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.deletedSuccessfully', ['id' => $option->id]),
        ]);

        $this->assertDatabaseMissing('category_option', [
            'category_id' => $category->id,
            'option_id'   => $option->id,
        ]);
    }
}
