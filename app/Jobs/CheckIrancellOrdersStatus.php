<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\V1\Esaj\EsajService;
use App\Services\V1\Esaj\Irancell;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckIrancellOrdersStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $idsForOperators = DB::table('orders')
            ->join('products', 'products.id', '=', DB::raw("JSON_UNQUOTE(JSON_EXTRACT(orders.detail, '$.product_id'))"))
            ->join('operators', 'operators.id', '=', 'products.operator_id')
            ->where('operators.name', 'Irancell')
            ->where('orders.status', Order::STATUSRESERVED)
            ->select('id_for_operator')
            ->pluck('id_for_operator')
            ->filter();

        $esajService = new EsajService();
        $esajService->setGateway(new Irancell());

        $purchased = [];
        $notPurchased = [];

        foreach ($idsForOperators->chunk(50) as $chunk) {
            foreach ($chunk as $id) {
                $response = $esajService->checkStatus($id);
                if ($response === 'true') {
                    $purchased[] = $id;
                } elseif ($response === 'false') {
                    $notPurchased[] = $id;
                }
            }
        }

        Log::info('Irancell Check Completed', [
            'count' => $idsForOperators->count(),
            'CountPurchased' => count($purchased),
            'CountNotPurchased' => count($notPurchased),
            'purchased' => $purchased,
            'notPurchased' => $notPurchased,
        ]);
    }
}
