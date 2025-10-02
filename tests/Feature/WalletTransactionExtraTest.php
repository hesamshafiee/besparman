<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\WalletTransactionExtra;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Illuminate\Testing\Fluent\AssertableJson;

class WalletTransactionExtraTest extends TestCase
{
    use RefreshDatabase;

    private $walletTransaction;
    private $role;

    public function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->artisan('db:seed');

        $user = User::factory()->create();

        $this->role = Role::create(['name' => 'role', 'guard_name' => 'web']);
        $this->role->givePermissionTo('walletTransaction.*');

        Sanctum::actingAs(
            $user,
            ['*']
        );

        $user->assignRole($this->role);


        $this->post('/api/wallet/increase-by-admin', ['userId' => Auth::id(), 'value' => 1000000,
            'message' => 'test test test test test test'])->assertStatus(200);

        $this->walletTransaction = WalletTransaction::first();
    }

    public function test_updating_wallet_transaction_extra() : void
    {
        $walletTransactionExtra = WalletTransactionExtra::factory()->create(['user_id' => Auth::id(), 'wallet_transaction_id' => $this->walletTransaction->id, 'mobile' => Auth::user()->mobile]);

        $response = $this->patch('/api/wallet/transaction/extras/' . $walletTransactionExtra->id, [
            'taken_value' => 20000,
        ]);

        $response->assertStatus(200)->assertJson([
            'status' => true,
            'message' => __('general.updatedSuccessfully', ['id' => $walletTransactionExtra->id]),
        ]);
    }

    public function test_fetching_wallet_transaction_extra() : void
    {
        WalletTransactionExtra::factory()->create(['user_id' => Auth::id(), 'wallet_transaction_id' => $this->walletTransaction->id, 'mobile' => Auth::user()->mobile]);

        $response = $this->get('/api/wallet/transaction/extras');

        $response->assertStatus(200)->assertJson(fn (AssertableJson $json) =>
            $json->hasAll(['data', 'links', 'meta',  'balance',  'additional'])
        );
    }
}
