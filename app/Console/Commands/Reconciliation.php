<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\V1\Esaj\EsajService;
use App\Services\V1\Esaj\Irancell;
use App\Services\V1\Financial\Financial;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Reconciliation extends Command
{
    protected $signature = 'app:reconciliation';
    protected $description = 'Perform reconciliation for wallet transactions with third-party info';

    public function handle()
    {
        $this->info("Starting reconciliation...");

        $query = WalletTransaction::whereNull('third_party_status')
            ->whereNull('third_party_info')
            ->where('detail', WalletTransaction::DETAIL_DECREASE_PURCHASE_BUYER)
            ->where('created_at', '<', now()->subMinutes(10))
            ->where('operator_id', 2);

        $count = (clone $query)->count();
        $this->info("Found $count matching transactions.");

        $esajService = new EsajService();
        $esajService->setGateway(new Irancell());

        $query->chunk(50, function ($transactions) use ($esajService) {
            foreach ($transactions as $walletTransaction) {
                try {
                    $walletTransactionsCount = WalletTransaction::where('order_id', $walletTransaction->order_id)->count() ?? 100;
                    $order = Order::find($walletTransaction->order_id);

                    if (!$order) {
//                        Log::warning("Order not found for transaction ID: {$walletTransaction->id}");
                        $this->info("Order not found for transaction ID: {$walletTransaction->id}");
                        continue;
                    }

                    $status = $esajService->checkStatus($order->id_for_operator);
                    $detail = json_decode($order->detail, true);
                    $productId = $detail['product_id'] ?? null;
                    $takenValue = $detail['takenValue'] ?? null;
                    $product = Product::find($productId);
                    $user = User::find($walletTransaction->user_id);

                    if (!$product || !$user) {
//                        Log::warning("Missing product/user for transaction ID: {$walletTransaction->id}");
                        $this->info("Missing product/user for transaction ID: {$walletTransaction->id}");

                        continue;
                    }

                    if ($status === 'true' || $status === 'false') {
                        $status = $status === 'true' ? 1 : 0;
                        if ($walletTransactionsCount === 1) {
                            DB::transaction(function () use ($status, $walletTransaction, $takenValue, $order, $product, $user) {
                                Financial::transactionsAfterTopUp(
                                    $walletTransaction->user_id,
                                    $walletTransaction->order_id,
                                    [],
                                    $status,
                                    '0' . substr($walletTransaction->charged_mobile, 2),
                                    $product,
                                    $takenValue
                                );

                                if (!$status) {
                                    Financial::operatorStatusFalse($order, $user);
//                                    Log::info("Operator returned false for Order ID: {$order->id}");
                                    $this->info("Operator returned false for Order ID: {$order->id}");
                                    return;
                                }

                                $prices = Financial::calculateProfit($product, $user->mobile, $walletTransaction->original_price, $walletTransaction->main_page);

                                Financial::handleEsajProfit(
                                    $prices['esaj_price'],
                                    $prices['esaj_profit'],
                                    $order,
                                    $product,
                                    $walletTransaction->main_page,
                                    $walletTransaction->groupId ?? null,
                                    null,
                                    $status
                                );

                                Financial::calculateUserPoints($user, $product, $order);

//                                Log::info("Handled single transaction and profit for Order ID: {$order->id}");
                                $this->info("Handled single transaction and profit for Order ID: {$order->id}");
                            });
                        } elseif (
                            $walletTransaction->detail === WalletTransaction::DETAIL_DECREASE_PURCHASE_BUYER &&
                            $walletTransaction->third_party_status === null &&
                            $walletTransaction->third_party_info === null &&
                            $status &&
                            $walletTransactionsCount > 1
                        ) {
                            DB::transaction(function () use ($status, $walletTransaction, $takenValue, $order, $product, $user) {
                                Financial::transactionsAfterTopUp(
                                    $walletTransaction->user_id,
                                    $walletTransaction->order_id,
                                    [],
                                    $status,
                                    0 . substr($walletTransaction->charged_mobile, 2),
                                    $product,
                                    $takenValue
                                );
                            });
                        }
                    }

                } catch (\Throwable $e) {
                    Log::info("Failed to process transaction ID: {$walletTransaction->id}. Error: " . $e->getMessage());
                    $this->info("Failed to process transaction ID: {$walletTransaction->id}. Error: " . $e->getMessage());
                }
            }
        });

        $this->info("Reconciliation complete.");
    }
}
