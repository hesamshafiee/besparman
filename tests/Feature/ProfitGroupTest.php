<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Profit;
use App\Models\ProfitGroup;
use App\Models\ProfitSplit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class ProfitGroupTest extends TestCase
{
    use RefreshDatabase;

    private int $profitId;
    private int $profitSplitId;
    private array $profitSplitIds;
    private profitGroup $profitGroup;
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

        $operator = Operator::first();
        $profit1 = Profit::factory(1)->create(['operator_id' => $operator->id, 'type' => Profit::TYPE_TD_LTE_INTERNET_PACKAGE]);
        $profit2 = Profit::factory(1)->create(['operator_id' => $operator->id, 'type' => Profit::TYPE_CELL_CARD_CHARGE]);
        $this->profitId = $profit1[0]->id;
        $profitSplit = ProfitSplit::factory()->create(['profit_id' => $this->profitId]);
        $this->profitSplitId = $profitSplit->id;
        $profitSplits1 = ProfitSplit::factory(1)->create(['profit_id' => $profit2[0]->id]);
        $profitSplits2 = ProfitSplit::factory(1)->create(['profit_id' => $profit1[0]->id]);
        $this->profitSplitIds = [$profitSplits1[0]->id, $profitSplits2[0]->id];
        $this->profitGroup = profitGroup::factory()->create(['profit_split_ids' => [$this->profitSplitId]]);
    }

    /**
     * @return void
     */
    public function test_storing_and_deleting_profitGroup(): void
    {
        $response = $this->post('/api/profit-groups', [
            'profit_split_ids' => $this->profitSplitIds,
            'title' => 'test',
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.savedSuccessfully'),
        ]);

        $profitGroup = profitGroup::first();

        //deleting
        $response = $this->delete('/api/profit-groups/' . $profitGroup->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $profitGroup->id]),
        ]);
    }

    /**
     * @return void
     */
    public function test_updating_profitGroup(): void
    {
        $response = $this->patch('/api/profit-groups/' . $this->profitGroup->id, [
            'profit_split_ids' => [$this->profitSplitId],
            'title' => 'test2',
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $this->profitGroup->id]),
        ]);
    }

    /**
     * @return void
     */
    public function test_fetching_profitGroups(): void
    {
        $response = $this->get('/api/profit-groups');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
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
        $response = $this->post('/api/profit-groups/assign-profit-group-to-user/' . $this->user->id, ['profit_group_id' => $this->profitGroup->id]);

        $response2 = $this->get('/api/profit-groups/user');

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => 'Profit group has been assigned to user',
        ]);
    }
}
