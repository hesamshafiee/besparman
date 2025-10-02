<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\V1\Image\Image;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;
use function PHPUnit\Framework\assertTrue;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user = User::factory()->create();
        $role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $role->givePermissionTo('user.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );

        $this->user->assignRole($role);
    }

    /**
     * @return void
     */
    public function test_fetching_users(): void
    {
        User::factory()->create();

        $response = $this->get('/api/users');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = User::first();

        $response2 = $this->get('/api/users?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_fetching_users_only_soft_deleted(): void
    {
        User::factory()->create();

        $response = $this->get('/api/users-soft-deleted');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );
    }
    public function test_storing_and_deleting_users(): void
    {
        $response = $this->post('/api/users', [
            'mobile' => '989121234567'
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $user = User::factory()->create();

        $response = $this->delete('/api/users/' . $user->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $user->id]),
        ]);

        $response2 = $this->get('/api/users/restore/'. $user->id);
        $response2->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $user->id]),
        ]);
    }

    public function test_updating_users(): void
    {
        $user = User::factory()->create();

        $response = $this->patch('/api/users/' . $user->id, [
            'mobile' => '989121234567',
            'name' => 'تست',
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $user->id]),
        ]);
    }

    /**
     * @return void
     */
    public function test_adding_images(): void
    {
        $image = UploadedFile::fake()->image('photo.png');
        $this->post('/api/users/add-images', ['images' => [$image]])->assertStatus(200);

        $images = Image::imageList($this->user, Image::DRIVER_LOCAL);
        assertTrue(count($images->getData()->paths) > 0);
    }
}
