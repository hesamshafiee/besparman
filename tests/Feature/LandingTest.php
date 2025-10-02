<?php

namespace Tests\Feature;

use App\Models\Landing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $user = User::factory()->create();
        $role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $role->givePermissionTo('landing.*');

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $user->assignRole($role);
    }

    public function test_storing_and_deleting_landing(): void
    {
        $response = $this->post('/api/landings', [
            'title' => 'test',
            'content' => json_encode(["key" => 'test'])
        ]);

        $response->assertStatus(200);

        $landing = Landing::first();

        //deleting
        $response = $this->delete('/api/landings/' . $landing->id);

        $response->assertStatus(200);
    }

    public function test_updating_landing() : void
    {
        $landing = Landing::factory()->create();

        $response = $this->patch('/api/landings/' . $landing->id, [
            'title' => 'test',
            'content' => json_encode(["key" => 'test'])
        ]);

        $response->assertStatus(200);
    }

    public function test_fetching_landings() : void
    {
        Landing::factory()->create();

        $response = $this->get('/api/landings');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Landing::first();

        $response2 = $this->get('/api/landings?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_fetching_clients_landings() : void
    {
        Landing::factory()->create(['status' => 1]);

        $response = $this->get('/api/clients/landings');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = Landing::where('status', 1)->first();

        $response2 = $this->get('/api/clients/landings?id=' . $firstModel->title);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
