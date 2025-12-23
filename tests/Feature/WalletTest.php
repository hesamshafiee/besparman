<?php

namespace Tests\Feature;

use App\Models\Operator;
use App\Models\Product;
use App\Models\Profit;
use App\Models\ProfitGroup;
use App\Models\ProfitSplit;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WalletTransactionExtra;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $secondUser;
    private $permission;
    private $role;

    const SUPER_ADMIN = 'super-admin';

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $this->user = User::factory()->create(['type' => 'panel', 'profile_confirm' => now()]);
        $this->secondUser = User::factory()->create(['type' => 'panel', 'profile_confirm' => now()]);
        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('walletTransaction.*');

        Sanctum::actingAs(
            $this->user,
            ['*']
        );

        $this->user->assignRole($this->role);
    }

    /**
     * @return void
     */
    public function test_fetching_transactions(): void
    {
        $response = $this->get('/api/wallet/transactions');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_fetching_clients_transactions() : void
    {
        $response = $this->get('/api/clients/wallet/transactions');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );

        $this->post('/api/wallet/increase-by-admin', ['userId' => $this->user->id, 'value' => '100000',
            'message' => 'test test test test test test']);

        $firstModel = WalletTransaction::first();

        $response2 = $this->get('/api/clients/wallet/transactions?id=' . $firstModel->id);

        $response2->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
        $json->hasAll(['data', 'balance', 'additional'])
        );
    }

    /**
     * @return void
     */
    public function test_increase_decrease_by_admin(): void
    {
        $response = $this->post('/api/wallet/increase-by-admin', ['userId' => $this->user->id, 'value' => '100000',
            'message' => 'test test test test test test']);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('increased successfully'),
        ]);

        $walletValue = Wallet::where('user_id', $this->user->id)->first()->value;
        self::assertTrue($walletValue === '100000.0000');

        $response = $this->post('/api/wallet/decrease-by-admin', ['userId' => $this->user->id, 'value' => '100000',
            'message' => 'test test test test test test']);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('decreased successfully'),
        ]);

        $walletValue = Wallet::where('user_id', $this->user->id)->first()->value;
        self::assertTrue($walletValue === '0.0000');
    }

    /**
     * @return void
     */
    public function test_confirm_transfer(): void
    {
        $this->post('/api/wallet/increase-by-admin', ['userId' => $this->user->id, 'value' => '200000',
            'message' => 'test test test test test test'])->assertStatus(200);

        $this->post('/api/wallet/transfer', ['mobile' => $this->secondUser->mobile, 'value' => '100000'])->assertStatus(200);

        $transactionId = WalletTransaction::where('detail', WalletTransaction::DETAIL_DECREASE_TRANSFER)->first()->id;

        $this->post('/api/wallet/confirm-transfer', ['transactionId' => $transactionId])->assertStatus(200);

        $transferFromUserWalletBalance = Wallet::where('user_id', $this->user->id)->first()->value;
        $transferToUserWalletBalance = Wallet::where('user_id', $this->secondUser->id)->first()->value;

        self::assertTrue($transferFromUserWalletBalance === '100000.0000' && $transferToUserWalletBalance === '100000.0000');
    }

    /**
     * @return void
     */
    public function test_reject_transfer(): void
    {
        $this->post('/api/wallet/increase-by-admin', ['userId' => $this->user->id, 'value' => '100000',
            'message' => 'test test test test test test'])->assertStatus(200);

        $this->post('/api/wallet/transfer', ['mobile' => $this->secondUser->mobile, 'value' => '100000'])->assertStatus(200);

        $transactionId = WalletTransaction::where('detail', WalletTransaction::DETAIL_DECREASE_TRANSFER)->first()->id;

        $this->post('/api/wallet/reject-transfer', ['transactionId' => $transactionId,
            'message' => 'test test test test test test'])->assertStatus(200);

        $transaction = WalletTransaction::find($transactionId);

        self::assertTrue($transaction->status === WalletTransaction::STATUS_REJECTED);
    }




}
