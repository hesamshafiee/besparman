<?php

namespace Tests\Feature;

use App\Models\ProfitGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class ProfitGroupTest extends TestCase
{
    use RefreshDatabase;

    private ProfitGroup $profitGroup;
    private User $user;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user = User::factory()->create();
        $role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $role->givePermissionTo('profit.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );

        $this->user->assignRole($role);

        // Create a default profit group for tests
        $this->profitGroup = ProfitGroup::factory()->create([
            'title'           => 'Default group',
            'designer_profit' => 40,
            'site_profit'     => 40,
            'referrer_profit' => 20,
        ]);
    }

    /**
     * @return void
     */
    public function test_storing_and_deleting_profitGroup(): void
    {
        $response = $this->post('/api/profit-groups', [
            'title'           => 'test',
            'designer_profit' => 30,
            'site_profit'     => 50,
            'referrer_profit' => 20,
        ]);

        $response->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $profitGroup = ProfitGroup::latest('id')->first();

        // deleting
        $response = $this->delete('/api/profit-groups/' . $profitGroup->id);

        $response->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.deletedSuccessfully', ['id' => $profitGroup->id]),
        ]);
    }

    /**
     * @return void
     */
    public function test_updating_profitGroup(): void
    {
        $response = $this->patch('/api/profit-groups/' . $this->profitGroup->id, [
            'title'           => 'test2',
            'designer_profit' => 50,
            'site_profit'     => 30,
            'referrer_profit' => 20,
        ]);

        $response->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => __('general.updatedSuccessfully', ['id' => $this->profitGroup->id]),
        ]);

        $this->assertDatabaseHas('profit_groups', [
            'id'              => $this->profitGroup->id,
            'title'           => 'test2',
            'designer_profit' => 50,
            'site_profit'     => 30,
            'referrer_profit' => 20,
        ]);
    }

    /**
     * @return void
     */
    public function test_fetching_profitGroups(): void
    {
        $response = $this->get('/api/profit-groups');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta', 'balance', 'additional'])
        );

        $firstModel = ProfitGroup::first();

        $response2 = $this->get('/api/profit-groups?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_assigning_profit_group_to_user(): void
    {
        $response = $this->post(
            '/api/profit-groups/assign-profit-group-to-user/' . $this->user->id,
            ['profit_group_id' => $this->profitGroup->id]
        );

        $response->assertStatus(200)->assertJson([
            'status'  => true,
            'message' => 'Profit group has been assigned to user',
        ]);

        $response2 = $this->get('/api/profit-groups/user');

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}

