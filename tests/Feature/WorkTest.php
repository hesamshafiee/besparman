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
        ]);

        $w2 = Work::create([
            'user_id' => $this->adminUser->id,
            'title' => 'Beta',
            'slug' => Work::makeSlug('Beta'),
            'description' => 'desc B',
        ]);

        $w2->delete();

        $response = $this->getJson('/api/works?with_trashed=1&order=id&type_order=asc&per_page=10');

        $response->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        $response = $this->getJson('/api/works?only_trashed=1');

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
        ]);

        $res = $this->getJson('/api/works?id=' . (int)$work->id);

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
        ]);

        $del = $this->deleteJson('/api/works/' . $work->id);
        $del->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $work->id]),
        ]);

        $this->assertSoftDeleted('works', ['id' => $work->id]);

        // بازیابی (route: POST /works/{work} -> restore)
        $restore = $this->postJson('/api/works/' . $work->id);
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
        ];

        $res = $this->postJson('/api/clients/works', $payload);

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
        ]);

        $res = $this->patchJson('/api/clients/works/' . $work->id, [
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
        ]);

        $mine2 = Work::create([
            'user_id' => $this->clientUser->id,
            'title' => 'M2',
            'slug' => Work::makeSlug('M2'),
            'description' => 'd2',
        ]);

        $list = $this->getJson('/api/clients/works?order=id&type_order=desc&per_page=10');
        $list->assertStatus(200)->assertJson(
            fn(AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        $show = $this->getJson('/api/clients/works?id=' . $mine1->id);
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
        ]);

        $res = $this->deleteJson('/api/clients/works/' . $work->id);

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
        ]);

        $upd = $this->patchJson('/api/clients/works/' . $othersWork->id, [
            'title' => 'Hack',
        ]);

        $upd->assertStatus(403)
            ->assertJson([
                'message' => __('general.forbidden'),
            ]);


        $del = $this->deleteJson('/api/clients/works/' . $othersWork->id);

        $del->assertStatus(403)
            ->assertJson([
                'message' => __('general.forbidden'),
            ]);
    }


    public function test_admin_can_update_publish_status(): void
    {
        Sanctum::actingAs($this->adminUser, ['*']);

        $work = Work::create([
            'user_id' => $this->adminUser->id,
            'title' => 'Publish Test',
            'slug' => Work::makeSlug('Publish Test'),
            'description' => 'pub',
        ]);

        // تغییر به 1
        $res = $this->patchJson('/api/works/publish/' . $work->id, [
            'is_published' => 1
        ]);

        $res->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('work.publishStatusUpdatedSuccessfully'),
        ]);

        $this->assertDatabaseHas('works', [
            'id' => $work->id,
            'is_published' => 1,
        ]);

        // تغییر به 2
        $res2 = $this->patchJson('/api/works/publish/' . $work->id , [
            'is_published' => 2
        ]);

        $res2->assertStatus(200);
        $this->assertDatabaseHas('works', [
            'id' => $work->id,
            'is_published' => 2,
        ]);
    }

}
