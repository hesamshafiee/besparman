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

    /**
     * @return void
     */
    public function test_pay_without_cart_with_profit_without_presenter(): void
    {

        $profitGroup = ProfitGroup::find(4);
        $ids = $profitGroup->profit_split_ids;
        foreach ($ids as $id) {
            $profitSplitId = $id;

            $profitSplit = ProfitSplit::find($profitSplitId);

            $profit = Profit::find($profitSplit->profit_id);
            if ($profit->type === Product::TYPE_CELL_DIRECT_CHARGE) {
                break;
            }
        }

        $this->user->profitGroups()->sync($profitGroup->id);

        $product = Product::where('operator_id', $profit->operator_id)->where('type', $profit->type)->first();

        $this->postJson('/api/wallet/increase-by-admin', [
            'userId' => $this->user->id,
            'value' => $product->price,
            'message' => 'Funds added for testing'
        ])->assertStatus(200);

        $response = $this->withHeaders([
            'Idempotency-Key' => 'unique-key-1'
        ])->postJson('/api/top-up/', [
            'mobile' => '989121112233',
            'price' => $product->price,
            'taken_value' => 10000,
            'product_id' => $product->id,
            'fake_response' => true
        ])->assertStatus(200);


        $userWalletValue = Wallet::where('user_id', $this->user->id)->first()->value;
        $expectedWalletValue = $product->price * ($profitSplit->seller_profit / 100);
        $expectedWalletValue = bcmul($expectedWalletValue, 1 , 4);
        $this->assertEquals($expectedWalletValue, $userWalletValue);
        $this->assertEquals(WalletTransaction::count(), 3);

        $walletTransactions = WalletTransaction::all();

        foreach ($walletTransactions as $walletTransaction) {
            $user = User::find($walletTransaction->user_id);
            $walletValue = $user->wallet->value;
            if ($walletTransaction->type === WalletTransaction::TYPE_DECREASE) {
                $this->assertEquals($walletTransaction->wallet_value_after_transaction, $walletValue);
                $this->assertEquals($walletTransaction->value, bcsub($product->price, $walletValue, 4));
                $this->assertEquals($walletTransaction->original_price, $product->price);
                $this->assertEquals($walletTransaction->profit, $product->price * ($profitSplit->seller_profit / 100));
                $this->assertEquals($walletTransaction->status, WalletTransaction::STATUS_CONFIRMED);
                $this->assertEquals($walletTransaction->detail, WalletTransaction::DETAIL_DECREASE_PURCHASE_BUYER);
                $this->assertEquals($walletTransaction->user_type, User::TYPE_PANEL);
                self::assertTrue($walletTransaction->checkSign());
            } else {
                if ($user->isAdminOrEsaj()) {
                    $this->assertEquals($walletTransaction->wallet_value_after_transaction, $product->price - ($product->price * ($profitSplit->seller_profit / 100)));
                    $this->assertEquals($walletTransaction->value, $walletValue);
                    $this->assertEquals($walletTransaction->original_price, null);
                    $this->assertEquals($walletTransaction->profit, intval($product->price * (($profit->profit - $profitSplit->seller_profit ) / 100)));
                    $this->assertEquals($walletTransaction->status, WalletTransaction::STATUS_CONFIRMED);
                    $this->assertEquals($walletTransaction->detail, WalletTransaction::DETAIL_INCREASE_PURCHASE_ESAJ);
                    $this->assertEquals($walletTransaction->user_type, User::TYPE_ESAJ);
                    self::assertTrue($walletTransaction->checkSign());
                } else {
                    $this->assertEquals($walletTransaction->wallet_value_after_transaction, $product->price);
                    $this->assertEquals($walletTransaction->value, $product->price);
                    $this->assertEquals($walletTransaction->original_price, null);
                    $this->assertEquals($walletTransaction->profit, null);
                    $this->assertEquals($walletTransaction->status, WalletTransaction::STATUS_CONFIRMED);
                    $this->assertEquals($walletTransaction->detail, WalletTransaction::DETAIL_INCREASE_ADMIN);
                    $this->assertEquals($walletTransaction->user_type, null);
                    self::assertTrue($walletTransaction->checkSign());
                }
            }
        }

    }

    /**
     * @return void
     */
    public function test_pay_without_cart_without_profit_without_presenter(): void
    {
        $product = Product::find(1);

        $profit = Profit::where('operator_id', $product->operator_id)->where('type', $product->type)->where('status', 1)->first();


        $this->postJson('/api/wallet/increase-by-admin', [
            'userId' => $this->user->id,
            'value' => $product->price,
            'message' => 'Funds added for testing'
        ])->assertStatus(200);

        $response = $this->withHeaders([
            'Idempotency-Key' => 'unique-key-2'
        ])->postJson('/api/top-up/', [
            'mobile' => '989121112233',
            'price' => $product->price,
            'taken_value' => 10000,
            'product_id' => $product->id,
            'fake_response' => true
        ]);

        $this->assertEquals(WalletTransaction::count(), 3);

        $walletTransactions = WalletTransaction::all();

        foreach ($walletTransactions as $walletTransaction) {
            $user = User::find($walletTransaction->user_id);
            $walletValue = $user->wallet->value;
            if ($walletTransaction->type === WalletTransaction::TYPE_DECREASE) {
                $this->assertEquals($walletTransaction->wallet_value_after_transaction, 0);
                $this->assertEquals($walletTransaction->value, $product->price);
                $this->assertEquals($walletTransaction->original_price, $product->price);
                $this->assertEquals($walletTransaction->profit, 0);
                $this->assertEquals($walletTransaction->status, WalletTransaction::STATUS_CONFIRMED);
                $this->assertEquals($walletTransaction->detail, WalletTransaction::DETAIL_DECREASE_PURCHASE_BUYER);
                $this->assertEquals($walletTransaction->user_type, User::TYPE_PANEL);
                self::assertTrue($walletTransaction->checkSign());
            } else {
                if ($user->isAdminOrEsaj()) {
                    $this->assertEquals($walletTransaction->wallet_value_after_transaction, $product->price);
                    $this->assertEquals($walletTransaction->value, $walletValue);
                    $this->assertEquals($walletTransaction->original_price, null);
                    $this->assertEquals($walletTransaction->profit, intval($product->price * ($profit->profit / 100)));
                    $this->assertEquals($walletTransaction->status, WalletTransaction::STATUS_CONFIRMED);
                    $this->assertEquals($walletTransaction->detail, WalletTransaction::DETAIL_INCREASE_PURCHASE_ESAJ);
                    $this->assertEquals($walletTransaction->user_type, User::TYPE_ESAJ);
                    self::assertTrue($walletTransaction->checkSign());
                } else {
                    $this->assertEquals($walletTransaction->wallet_value_after_transaction, $product->price);
                    $this->assertEquals($walletTransaction->value, $product->price);
                    $this->assertEquals($walletTransaction->original_price, null);
                    $this->assertEquals($walletTransaction->profit, null);
                    $this->assertEquals($walletTransaction->status, WalletTransaction::STATUS_CONFIRMED);
                    $this->assertEquals($walletTransaction->detail, WalletTransaction::DETAIL_INCREASE_ADMIN);
                    $this->assertEquals($walletTransaction->user_type, null);
                    self::assertTrue($walletTransaction->checkSign());
                }
            }
        }
    }
}
