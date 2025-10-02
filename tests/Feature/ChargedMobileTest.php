<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;


class ChargedMobileTest extends TestCase
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

        $this->user = User::factory()->create(['type' => 'panel', 'profile_confirm' => now()]);
        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);

        Sanctum::actingAs(
            $this->user,
            ['*']
        );

    }


    public function test_returns_true_if_mobile_exists_for_the_user_and_not_exist(): void
    {
        DB::table('charged_mobiles')->insert([
            'user_id'    => $this->user->id,
            'mobile'     => '989123456789',
        ]);


        $response = $this->getJson('/api/clients/user/mobile-charged-before/989123456789');

        $response->assertStatus(200)
                 ->assertExactJson(['charged_before' => true]);
    
        $response = $this->getJson('/api/clients/user/mobile-charged-before/989120000000');

        $response->assertStatus(200)
                 ->assertExactJson(['charged_before' => false]);

        
    }

}
