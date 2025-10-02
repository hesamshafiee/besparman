<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use function PHPUnit\Framework\assertNotTrue;
use function PHPUnit\Framework\assertTrue;

class AclTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $permission;
    private $role;

    const SUPER_ADMIN = 'super-admin';

    /**
     * @return void
     */
    public function setUp() :void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user = User::factory()->create();

        $this->permission = Permission::create(['name' => 'test', 'guard_name' => 'web']);
        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);


        Sanctum::actingAs(
            $this->user,
            ['*']
        );
    }

    /**
     * @return void
     */
    public function test_fetching_acl_permission_and_roles() : void
    {
        $this->user->assignRole(self::SUPER_ADMIN);

        $response = $this->get('/api/acl');
        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['roles', 'permissions'])
        );
    }

    /**
     * @return void
     */
    public function test_fetching_acl_permission_from_specific_role() : void
    {
        $this->user->assignRole(self::SUPER_ADMIN);

        $role = Role::first();

        $role->givePermissionTo(['user.create']);

        $response = $this->get('/api/acl/get-role-permissions/' . $role->id);

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['permissionsOfRole', 'roleId', 'role'])
        );
    }

    /**
     * @return void
     */
    public function test_fetching_acl_permission_and_roles_without_permission() :void
    {
        $response = $this->get('/api/acl');
        $response->assertStatus(403);
    }

    /**
     * @return void
     */
    public function test_give_permission_to_role() : void
    {
        $this->user->assignRole(self::SUPER_ADMIN);
        $response = $this->post('/api/acl/give-permission-to-role', ['role' => $this->role->name, 'permissions' => [$this->permission->name]]);
        $response->assertStatus(200);

        $result = Role::whereHas('permissions', function (Builder $query) {
            $query->where('name', $this->permission->name);
        })->first();

        $this->assertTrue($result->name === $this->role->name);
    }

    /**
     * @return void
     */
    public function test_revoke_permission_to_role() : void
    {
        $this->user->assignRole(self::SUPER_ADMIN);

        $this->role->givePermissionTo($this->permission->name);

        $response = $this->post('/api/acl/revoke-permission-to-role', ['role' => $this->role->name, 'permissions' => [$this->permission->name]]);
        $response->assertStatus(200);

        $result = Role::whereHas('permissions', function (Builder $query) {
            $query->where('name', $this->permission->name);
        })->first();

        $this->assertNull($result);
    }

    /**
     * @return void
     */
    public function test_assign_role_to_user() : void
    {
        $this->user->assignRole(self::SUPER_ADMIN);
        $response = $this->post('/api/acl/assign-role-to-user', ['role' => $this->role->name, 'user' => $this->user->id]);
        $response->assertStatus(200);

        $result = $this->user->roles()->where('name', $this->role->name)->first();
        $this->assertTrue($result->name === $this->role->name);
    }

    /**
     * @return void
     */
    public function test_remove_role_to_user() : void
    {
        $this->user->assignRole(self::SUPER_ADMIN);
        $this->role->givePermissionTo($this->permission->name);

        $response = $this->post('/api/acl/remove-role-to-user', ['role' => $this->role->name, 'user' => $this->user->id]);
        $response->assertStatus(200);

        $result = $this->user->roles()->where('name', $this->role->name)->first();
        $this->assertNull($result);
    }

    /**
     * @return void
     */
    public function test_create_role() : void
    {
        $this->user->assignRole(self::SUPER_ADMIN);

        $response = $this->post('/api/acl/create-role', ['role' => 'role10']);
        $response->assertStatus(200);

        assertTrue(Role::where('name', 'role10')->exists());
    }

    /**
     * @return void
     */
    public function test_update_role() : void
    {
        $this->user->assignRole(self::SUPER_ADMIN);
        $role = Role::create(['name' => 'test', 'guard_name' => 'web']);

        $response = $this->patch('/api/acl/update-role/' . $role->id, ['role' => 'role20']);
        $response->assertStatus(200);

        assertTrue(Role::where('name', 'role20')->exists());
    }

    /**
     * @return void
     */
    public function test_deleting_role(): void
    {
        $this->user->assignRole(self::SUPER_ADMIN);
        $role = Role::create(['name' => 'test', 'guard_name' => 'web']);

        $response = $this->delete('/api/acl/role/' . $role->id);
        $response->assertStatus(200);

        assertNotTrue(Role::where('name', 'role10')->exists());
    }
}
