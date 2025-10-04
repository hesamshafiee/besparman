<?php

namespace App\Jobs;

use App\Models\ReportDailyUser;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\UTCDateTime;

class UserReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $date;

    public function __construct(string $date)
    {
        $this->date = $date;
    }

    public function handle()
    {
        $dateToUse = Carbon::parse($this->date);
        $startOfDay = $dateToUse->copy()->startOfDay();
        $endOfDay   = $dateToUse->copy()->endOfDay();

        // --- 1. Preload existing reports ---
        $existingReports = ReportDailyUser::whereBetween(
            'date',
            [new UTCDateTime($startOfDay->timestamp * 1000), new UTCDateTime($endOfDay->timestamp * 1000)]
        )->pluck('user_id')->toArray();

        // --- 2. Aggregate user totals in one query ---
        $totals = DB::table('wallet_transactions as wt')
            ->join('users as u', 'wt.user_id', '=', 'u.id')
            ->join('profiles as p', 'u.profile_id', '=', 'p.id')
            ->select(
                'wt.user_id',
                'u.mobile',
                'u.name',
                'p.national_code',
                'p.address',
                'p.city',
                'p.province',
                'p.postal_code',
                DB::raw('SUM(wt.value) as total_value'),
                DB::raw('SUM(wt.original_price) as total_original_price'),
                DB::raw('COUNT(*) as total_count')
            )
            ->where('wt.detail', 'decrease_purchase_buyer')
            ->where('wt.third_party_status', 1)
            ->where('wt.type', 'decrease')
            ->whereBetween('wt.created_at', [$startOfDay, $endOfDay])
            ->groupBy(
                'wt.user_id',
                'u.mobile',
                'u.name',
                'p.national_code',
                'p.address',
                'p.city',
                'p.province',
                'p.postal_code'
            )
            ->get()
            ->keyBy('user_id');

        if ($totals->isEmpty()) {
            return;
        }

        // --- 3. Aggregate operator breakdown ---
        $operators = DB::table('wallet_transactions as wt')
            ->join('operators as o', 'wt.operator_id', '=', 'o.id')
            ->leftJoin(
                'products as pr',
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(wt.extra_info, '$.product_id'))"),
                '=',
                'pr.id'
            )
            ->select(
                'wt.user_id',
                'wt.operator_id',
                'o.name as operator_name',
                DB::raw('SUM(wt.value) as total'),
                DB::raw('SUM(wt.original_price) as total_original_price'),
                DB::raw('COUNT(*) as total_count')
            )
            ->where('wt.detail', 'decrease_purchase_buyer')
            ->where('wt.third_party_status', 1)
            ->where('wt.type', 'decrease')
            ->whereBetween('wt.created_at', [$startOfDay, $endOfDay])
            ->whereNotNull('wt.operator_id')
            ->groupBy('wt.user_id', 'wt.operator_id', 'o.name')
            ->get()
            ->groupBy('user_id');

        // --- 4. Build insert payload ---
        $results = [];

        foreach ($totals as $userId => $item) {
            if (in_array($userId, $existingReports, true)) {
                continue;
            }

            $userOperators = $operators->get($userId, collect());

            $opMap = $userOperators->mapWithKeys(function ($op) {
                return [$op->operator_name => $op];
            });

            $results[] = [
                'user_id' => (int) $userId,
                'mobile' => $item->mobile,
                'name' => $item->name,
                'national_code' => $item->national_code,
                'address' => $item->address,
                'city' => $item->city,
                'province' => $item->province,
                'postal_code' => $item->postal_code,
                'total_value' => (float) $item->total_value,
                'original_price' => (float) $item->total_original_price,
                'total_count' => (int) $item->total_count,
                'operators' => $userOperators->toArray(),
                'irancell_total' => (float) ($opMap['Irancell']->total ?? 0),
                'irancell_total_original_price' => (float) ($opMap['Irancell']->total_original_price ?? 0),
                'mci_total' => (float) ($opMap['Mci']->total ?? 0),
                'mci_total_original_price' => (float) ($opMap['Mci']->total_original_price ?? 0),
                'rightel_total' => (float) ($opMap['Rightel']->total ?? 0),
                'rightel_total_original_price' => (float) ($opMap['Rightel']->total_original_price ?? 0),
                'aptel_total' => (float) ($opMap['Aptel']->total ?? 0),
                'aptel_total_original_price' => (float) ($opMap['Aptel']->total_original_price ?? 0),
                'shatel_total' => (float) ($opMap['Shatel']->total ?? 0),
                'shatel_total_original_price' => (float) ($opMap['Shatel']->total_original_price ?? 0),
                'date' => new UTCDateTime($startOfDay->timestamp * 1000),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // --- 5. Bulk insert ---
        if (!empty($results)) {
            // Insert in chunks to avoid huge queries
            collect($results)->chunk(1000)->each(function ($chunk) {
                ReportDailyUser::insert($chunk->toArray());
            });
        }
    }
}
