<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $user = User::factory()->create();
        $role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $role->givePermissionTo('profile.*');

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $user->assignRole($role);
    }

    /**
     * @return void
     */
    public function test_storing_profile(): void
    {
        $image = UploadedFile::fake()->image('photo.png');

        $response = $this->post('/api/profiles', [
            'province' => 'تهران',
            'city' => 'تهران',
            'birth_date' => fake()->date,
            'store_name' => '1 تست-_,',
            'address' => 'تست',
            'postal_code' => fake()->postcode,
            'national_code' => Str::random(10),
            'gender' => 'female',
            'images' => [$image],
            'name' => 'تست'
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $user = User::factory()->create();


        $response = $this->post('/api/profiles/by-admin/' . $user->id, [
            'province' => 'تهران',
            'city' => 'تهران',
            'birth_date' => fake()->date,
            'store_name' => 'تست',
            'address' => 'تست',
            'postal_code' => fake()->postcode,
            'national_code' => Str::random(10),
            'gender' => 'female',
            'images' => [$image],
            'name' => 'تست'
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $this->patch('/api/profiles/by-admin/' . $user->id, [
            'province' => 'تهران',
            'city' => 'تهران',
            'birth_date' => fake()->date,
            'store_name' => 'تس',
            'address' => 'تس',
            'postal_code' => fake()->postcode,
            'national_code' => Str::random(10),
            'gender' => 'female',
            'images' => [$image],
            'name' => 'تست'
        ])->assertStatus(200);
    }

    /**
     * @return void
     */
    public function test_updating_profile(): void
    {
        $image = UploadedFile::fake()->image('photo.png');

        $this->post('/api/profiles', [
            'province' => 'تهران',
            'city' => 'تهران',
            'birth_date' => fake()->date,
            'store_name' => 'تست',
            'address' => 'تست',
            'postal_code' => fake()->postcode,
            'national_code' => Str::random(10),
            'gender' => 'female',
            'images' => [$image],
            'name' => 'تست'
        ])->assertStatus(200);

        $response = $this->patch('/api/profiles', [
            'province' => 'تهران',
            'city' => 'تهران',
            'birth_date' => fake()->date,
            'store_name' => 'تس',
            'address' => 'تس',
            'postal_code' => fake()->postcode,
            'national_code' => Str::random(10),
            'gender' => 'female',
            'images' => [$image],
            'name' => 'تست'
        ]);

        $profile = Profile::first();

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $profile->id]),
        ]);
    }

    /**
     * @return void
     */
    public function test_fetching_profiles(): void
    {
        Profile::factory()->create();

        $response = $this->get('/api/profiles');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Profile::first();

        $response2 = $this->get('/api/profiles?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_fetching_logged_in_user_profile(): void
    {
        Profile::factory()->create();
        $profile = Profile::first();

        $user = Auth::user();
        $user->profile_id = $profile->id;
        $user->save();

        $response = $this->get('/api/profile');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
