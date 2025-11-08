<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Mockup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class MockupTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private mixed $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user = User::factory()->create();

        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('mockup.*');

        Sanctum::actingAs($this->user, ['*']);
    }

    public function test_storing_and_deleting_mockup(): void
    {
        $this->user->assignRole($this->role);

        $category = Category::factory()->create();

        $payload = [
            'category_id'   => $category->id,
            'name'          => fake()->sentence(2),
            // 'slug'        => اختیاری
            'canvas_width'  => 2400,
            'canvas_height' => 2400,
            'dpi'           => 150,
            'print_x'       => 200,
            'print_y'       => 220,
            'print_width'   => 1800,
            'print_height'  => 1600,
            'print_rotation'=> 0,
            'fit_mode'      => 'contain',
            'layers'        => json_encode([
                'base'    => 'mockups/base/sample.png',
                'overlay' => null,
                'shadow'  => null,
                'mask'    => null,
            ]),
            'preview_bg'    => '#FFFFFF',
            'is_active'     => 1,
            'sort'          => 10,
        ];

        $response = $this->post('/api/mockups', $payload);

        $response->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $mockup = Mockup::first();
        $this->assertNotNull($mockup);

        $response = $this->delete('/api/mockups/' . $mockup->id);

        $response->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.deletedSuccessfully', ['id' => $mockup->id]),
        ]);
    }

    public function test_updating_mockup(): void
    {
        $this->user->assignRole($this->role);

        $category = Category::factory()->create();

        $mockup = Mockup::create([
            'category_id'   => $category->id,
            'name'          => 'قدیمی',
            'slug'          => fake()->unique()->slug(),
            'canvas_width'  => 2000,
            'canvas_height' => 2000,
            'dpi'           => 150,
            'print_x'       => 100,
            'print_y'       => 100,
            'print_width'   => 1600,
            'print_height'  => 1600,
            'print_rotation'=> 0,
            'fit_mode'      => 'contain',
            'layers'        => ['base' => 'mockups/base/old.png'],
            'preview_bg'    => '#FFFFFF',
            'is_active'     => 1,
            'sort'          => 0,
        ]);

        $updatePayload = [
            'category_id'   => $category->id,
            'name'          => 'جدید',
            'canvas_width'  => 2200,
            'canvas_height' => 2200,
            'dpi'           => 150,
            'print_x'       => 150,
            'print_y'       => 150,
            'print_width'   => 1700,
            'print_height'  => 1700,
            'print_rotation'=> 0,
            'fit_mode'      => 'cover',
            'layers'        => json_encode(['base' => 'mockups/base/new.png']),
            'is_active'     => 1,
            'sort'          => 5,
        ];

        $response = $this->put('/api/mockups/' . $mockup->id, $updatePayload);

        $response->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.updatedSuccessfully', ['id' => $mockup->id]),
        ]);

        $this->assertDatabaseHas('mockups', [
            'id'       => $mockup->id,
            'name'     => 'جدید',
            'fit_mode' => 'cover',
            'sort'     => 5,
        ]);
    }

    public function test_fetching_mockups(): void
    {
        $this->user->assignRole($this->role);

        $category = Category::factory()->create();

        Mockup::create([
            'category_id'   => $category->id,
            'name'          => fake()->sentence(2),
            'slug'          => fake()->unique()->slug(),
            'canvas_width'  => 2000,
            'canvas_height' => 2000,
            'dpi'           => 150,
            'print_x'       => 100,
            'print_y'       => 100,
            'print_width'   => 1600,
            'print_height'  => 1600,
            'print_rotation'=> 0,
            'fit_mode'      => 'contain',
            'layers'        => ['base' => 'mockups/base/one.png'],
            'preview_bg'    => '#FFFFFF',
            'is_active'     => 1,
            'sort'          => 0,
        ]);

        $response = $this->get('/api/mockups?per_page=2');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        $first = Mockup::first();

        $response2 = $this->get('/api/mockups?id=' . $first->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
