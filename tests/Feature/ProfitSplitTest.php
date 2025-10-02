<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Profit;
use App\Models\ProfitSplit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class ProfitSplitTest extends TestCase
{
    use RefreshDatabase;

    private int $profitId;
    private ProfitSplit $profitSplit;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $user = User::factory()->create();
        $role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $role->givePermissionTo('profit.*');

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $user->assignRole($role);

        $operator = Operator::first();
        $profit = Profit::factory()->create(['operator_id' => $operator->id, 'type' => Profit::TYPE_CELL_CARD_CHARGE]);
        $this->profitId = $profit->id;
        $this->profitSplit = ProfitSplit::factory()->create(['profit_id' => $this->profitId]);
    }

    /**
     * @return void
     */
    public function test_storing_and_deleting_profitSplit(): void
    {
        $response = $this->post('/api/profit-splits', [
            'profit_id' => $this->profitId,
            'title' => 'test',
            'seller_profit' => 2,
        ]);

        $response->assertStatus(201);

        $profitSplit = ProfitSplit::first();

        //deleting
        $response = $this->delete('/api/profit-splits/' . $profitSplit->id);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.deletedSuccessfully', ['id' => $profitSplit->id]),
        ]);
    }

    /**
     * @return void
     */
    public function test_updating_profitSplit(): void
    {
        $response = $this->patch('/api/profit-splits/' . $this->profitSplit->id, [
            'profit_id' => $this->profitId,
            'title' => 'test2',
            'seller_profit' => 3,
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $this->profitSplit->id]),
        ]);
    }

    /**
     * @return void
     */
    public function test_fetching_profitSplits(): void
    {
        $response = $this->get('/api/profit-splits');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $firstModel = ProfitSplit::first();

        $response2 = $this->get('/api/profit-splits?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'balance', 'additional'])
        );
    }
}
