<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkTest extends TestCase
{
    use RefreshDatabase;

    private $adminUser;
    private $clientUser;
    private $role;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->adminUser = User::factory()->create([
            'type' => 'panel',
            'profile_confirm' => now(),
        ]);

        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);

        $this->role->givePermissionTo('work.*');
        $this->adminUser->assignRole($this->role);

        $this->clientUser = User::factory()->create([
            'type' => 'panel',
            'profile_confirm' => now(),
        ]);

        Sanctum::actingAs($this->adminUser, ['*']);
    }


    public function test_admin_can_list_works_with_pagination_and_sort_and_softdelete_flags(): void
    {
        $w1 = Work::create([
            'user_id' => $this->adminUser->id,
            'title' => 'Alpha',
            'slug' => Work::makeSlug('Alpha'),
            'description' => 'desc A',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $w2 = Work::create([
            'user_id' => $this->adminUser->id,
            'title' => 'Beta',
            'slug' => Work::makeSlug('Beta'),
            'description' => 'desc B',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $w2->delete();

        $response = $this->getJson('/api/work?with_trashed=1&order=id&type_order=asc&per_page=10');

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        $response = $this->getJson('/api/work?only_trashed=1');

        $response->assertStatus(200);
        $this->assertTrue(
            collect($response->json('data'))->contains(fn($i) => (int)$i['id'] === (int)$w2->id),
            'deleted work not found in only_trashed list'
        );
    }

    public function test_admin_can_fetch_single_work_by_id(): void
    {
        $work = Work::create([
            'user_id' => $this->adminUser->id,
            'title' => 'Single',
            'slug' => Work::makeSlug('Single'),
            'description' => 'one',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $res = $this->getJson('/api/work?id=' . (int)$work->id);

        $res->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->where('data.id', $work->id)->etc()
        );
    }

    public function test_admin_can_delete_and_restore_work(): void
    {
        $work = Work::create([
            'user_id' => $this->adminUser->id,
            'title' => 'ToDelete',
            'slug' => Work::makeSlug('ToDelete'),
            'description' => 'delete me',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $del = $this->deleteJson('/api/work/' . $work->id);
        $del->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $work->id]),
        ]);

        $this->assertSoftDeleted('works', ['id' => $work->id]);

        // بازیابی (route: POST /work/{work} -> restore)
        $restore = $this->postJson('/api/work/' . $work->id);
        $restore->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.restoredSuccessfully'),
        ]);

        $this->assertDatabaseHas('works', ['id' => $work->id, 'deleted_at' => null]);
    }


    public function test_client_can_store_work(): void
    {
        Sanctum::actingAs($this->clientUser, ['*']);

        $payload = [
            'title' => 'Client Post',
            'description' => 'client desc',
            'is_published' => true,
            'published_at' => now()->toISOString(),
        ];

        $res = $this->postJson('/api/clients/work', $payload);

        $res->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $this->assertDatabaseHas('works', [
            'title' => 'Client Post',
            'user_id' => $this->clientUser->id,
        ]);
    }

    public function test_client_can_update_own_work(): void
    {
        Sanctum::actingAs($this->clientUser, ['*']);

        $work = Work::create([
            'user_id' => $this->clientUser->id,
            'title' => 'Old Title',
            'slug' => Work::makeSlug('Old Title'),
            'description' => 'old',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $res = $this->patchJson('/api/clients/work/' . $work->id, [
            'title' => 'New Title',
            'description' => 'new',
            'is_published' => false,
        ]);

        $res->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully'),
        ]);

        $this->assertDatabaseHas('works', [
            'id' => $work->id,
            'title' => 'New Title',
            'is_published' => 0,
        ]);
    }

    public function test_client_index_list_and_show_by_id(): void
    {
        Sanctum::actingAs($this->clientUser, ['*']);

        $mine1 = Work::create([
            'user_id' => $this->clientUser->id,
            'title' => 'M1',
            'slug' => Work::makeSlug('M1'),
            'description' => 'd1',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $mine2 = Work::create([
            'user_id' => $this->clientUser->id,
            'title' => 'M2',
            'slug' => Work::makeSlug('M2'),
            'description' => 'd2',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $list = $this->getJson('/api/clients/work?order=id&type_order=desc&per_page=10');
        $list->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        $show = $this->getJson('/api/clients/work?id=' . $mine1->id);
        $show->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->where('data.id', $mine1->id)->etc()
        );
    }

    public function test_client_can_delete_own_work(): void
    {
        Sanctum::actingAs($this->clientUser, ['*']);

        $work = Work::create([
            'user_id' => $this->clientUser->id,
            'title' => 'ToRemove',
            'slug' => Work::makeSlug('ToRemove'),
            'description' => 'rm',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $res = $this->deleteJson('/api/clients/work/' . $work->id);

        $res->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $work->id]),
        ]);

        $this->assertSoftDeleted('works', ['id' => $work->id]);
    }

    public function test_client_cannot_modify_others_work(): void
    {
        Sanctum::actingAs($this->clientUser, ['*']);

        $otherUser = User::factory()->create([
            'type' => 'panel',
            'profile_confirm' => now(),
        ]);

        $othersWork = Work::create([
            'user_id' => $otherUser->id,
            'title' => 'Not Yours',
            'slug' => Work::makeSlug('Not Yours'),
            'description' => 'others',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $upd = $this->patchJson('/api/clients/work/' . $othersWork->id, [
            'title' => 'Hack',
        ]);

        $upd->assertStatus(403)
            ->assertJson([
                'message' => __('general.forbidden'),
            ]);


        $del = $this->deleteJson('/api/clients/work/' . $othersWork->id);

        $del->assertStatus(403)
            ->assertJson([
                'message' => __('general.forbidden'),
            ]);
    }
}
