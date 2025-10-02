<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Auth\AclRequest;
use App\Http\Resources\V1\AclResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;

class AclController extends Controller
{
    /**
     * @return JsonResponse
     * @group Acl
     */
    public function permissionsAndRoles() : JsonResponse
    {
        return response()->json([
            'permissions' => AclResource::collection(Permission::all()),
            'roles' => AclResource::collection(Role::all()),
        ], 200);
    }

    /**
     * @param AclRequest $request
     * @return JsonResponse
     * @group Acl
     */
    public function givePermissionTo(AclRequest $request) : JsonResponse
    {
        $role = Role::where('name', $request->role)->firstOrFail();
        $role->givePermissionTo($request->permissions);
        return response()->ok(__('auth.givePermissionToRole'));
    }


    /**
     *
     * @param Request $request
     * @return JsonResponse
     * @group Acl
     */
    public function syncPermissionsToRoles(Request $request) : JsonResponse
    {
         $rolesPermissions = $request->input('roles_permissions');
        foreach ($rolesPermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->syncPermissions($permissions);
            }
        }
        return response()->ok(__('auth.givePermissionToRole'));
    }


    /**
     * @param AclRequest $request
     * @return JsonResponse
     * @group Acl
     */
    public function revokePermissionTo(AclRequest $request) : JsonResponse
    {
        $role = Role::where('name', $request->role)->firstOrFail();
        $role->revokePermissionTo($request->permissions);
        return response()->ok(__('auth.revokePermissionToRole'));
    }

    /**
     * @param AclRequest $request
     * @return JsonResponse
     * @group Acl
     */
    public function assignRoleToUser(AclRequest $request) : JsonResponse
    {
        $user = User::findOrFail($request->user);
        $user->assignRole($request->role);
        return response()->ok(__('auth.assignRoleToUser'));
    }

    /**
     * @param AclRequest $request
     * @return JsonResponse
     * @group Acl
     */
    public function removeRoleToUser(AclRequest $request) : JsonResponse
    {
        $user = User::findOrFail($request->user);
        $user->removeRole($request->role);
        return response()->ok(__('auth.removeRoleToUser'));
    }

    /**
     * @param AclRequest $request
     * @return JsonResponse
     * @group Acl
     */
    public function createRole(AclRequest $request) : JsonResponse
    {
        $role  = new Role();
        $role->name = $request->role;
        $role->guard_name = 'web';

        if ($role->save()) {
            return response()->ok(__('auth.roleCreatedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param AclRequest $request
     * @param Role $role
     * @return JsonResponse
     * @group Acl
     */
    public function UpdateRole(AclRequest $request, Role $role) : JsonResponse
    {
        $role->name = $request->role;

        if ($role->save()) {
            return response()->ok(__('auth.updatedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }

    /**
     * @param Role $role
     * @return JsonResponse
     * @group Acl
     */
    public function deleteRole(Role $role) : JsonResponse
    {
        if ($role->delete()) {
            return response()->ok(__('auth.roleCreatedSuccessfully'));
        }

        return response()->serverError(__('general.somethingWrong'));
    }



    /**
     *
     * @param Role $role
     * @return JsonResponse
     * @group Acl
     */
    public function getRolePermissions(Role $role): JsonResponse
    {
        return response()->json([
            'roleId' => $role->id,
            'role' => $role->name,
            'permissionsOfRole' => $role->permissions->pluck('name', 'id')->toArray(),
        ], 200);
    }
}
