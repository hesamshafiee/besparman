<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private mixed $permission;
    private mixed $role;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user = User::factory()->create();
        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('setting.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );
    }

    /**
     * @return void
     */
    public function test_storing_and_deleting_setting(): void
    {
        $this->user->assignRole($this->role);

        $response = $this->post('/api/settings', [
            'sms' => 1,
            'front' => '{"name":"John", "age":30, "car":null}'
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $setting = Setting::first();

        //deleting
        $response = $this->delete('/api/settings/' . $setting->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $setting->id]),
        ]);
    }

    /**
     * @return void
     */
    public function test_updating_setting() : void
    {
        $this->user->assignRole($this->role);

        $this->post('/api/settings', [
            'sms' => 1,
        ])->assertStatus(200);

        $setting = Setting::first();

        $response = $this->patch('/api/settings/' . $setting->id, ['sms' => 1]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $setting->id]),
        ]);
    }

    /**
     * @return void
     */
    public function test_fetching_settings() : void
    {
        $this->user->assignRole($this->role);

        $response = $this->get('/api/settings/');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        Setting::factory()->create();

        $firstModel = Setting::first();

        $response2 = $this->get('/api/settings?id='. $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
