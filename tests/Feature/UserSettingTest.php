<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;


class UserSettingTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $role;
    private $adminUser;
    private $clientUser;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user = User::factory()->create(['type' => 'panel', 'profile_confirm' => now()]);

        Sanctum::actingAs(
            $this->user,
            ['*']
        );
    }



    public function test_storing_and_deleting_users_setting_client(): void
    {

        $userSettingData = [
            'user_id' => $this->user->id,
            'settings' => ['minimum_wallet' => 1000],
        ];

        $response = $this->postJson('/api/clients/user-settings', $userSettingData);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);
        $userSetting = UserSetting::first();

        $response = $this->delete('/api/clients/user-settings/' . $userSetting->id);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $userSetting->id]),
        ]);
    }


    public function test_updating_addresses_client(): void
    {
        $userSettingData = [
            'user_id' => $this->user->id,
            'settings' => ['minimum_wallet' => 1500],
        ];
        $response = $this->postJson('/api/clients/user-settings', $userSettingData);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);


        $userSetting = UserSetting::first();
        $response = $this->patchJson('/api/clients/user-settings/' . $userSetting->id, [
            'user_id' => $this->user->id,
            'settings' => ['minimum_wallet' => 10000],
        ]);
        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully'),
        ]);


    }

}
