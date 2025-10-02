<?php

namespace Tests\Feature;

use App\Models\Logistic;
use App\Models\PanelMessage;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class VersionTest extends TestCase
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
        $this->role->givePermissionTo('version.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );
    }
    /**
     * A basic feature test example.
     */
    public function test_storing_and_deleting_version(): void
    {
        $this->user->assignRole($this->role);

        $response = $this->post('/api/versions', [
            'version' => 'v1.0.1',
            'title' => fake()->title,
            'type' => 'panel',
            'description' => fake()->text(550),
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $Version = Version::first();

        //deleting
        $response = $this->delete('/api/versions/' . $Version->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $Version->id]),
        ]);
    }


    public function test_updating_version(): void
    {
        $this->user->assignRole($this->role);

        $version = Version::create([
            'version' => 'v1.0.1',
            'title' => fake()->title,
            'type' => 'panel',
            'description' => fake()->text(550),
        ]);

        $response = $this->patch('/api/versions/' . $version->id, [
            'version' => 'v1.0.1',
            'title' => $version->title,
            'type' => 'panel',
            'description' => fake()->text(550),
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $version->id]),
        ]);
    }


    public function test_fetching_version(): void
    {
        $this->user->assignRole($this->role);

        $version = Version::create([
            'version' => 'v1.0.1',
            'title' => fake()->title,
            'type' => 'panel',
            'description' => fake()->text(550),
        ]);

        $response = $this->get('/api/versions/');

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Version::first();

        $response2 = $this->get('/api/versions?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }



    public function test_latest_version_by_type(): void
    {
        $this->user->assignRole($this->role);

        $firstVersion = Version::create([
            'version' => 'v1.0.1',
            'title' => 'First Version',
            'type' => 'panel',
            'description' => 'First description',
        ]);

        $latestVersion = Version::create([
            'version' => 'v1.0.1',
            'title' => 'Latest Version',
            'type' => 'panel',
            'description' => 'Latest description',
        ]);

        $response = $this->getJson('/api/clients/versions/latest-by-type?type=panel');
        $response->assertStatus(200)
            ->assertJson(
                fn(AssertableJson $json) =>
                $json->where('data.id', $latestVersion->id)
                    ->where('data.title', $latestVersion->title)
                    ->etc()
            );
    }
}
