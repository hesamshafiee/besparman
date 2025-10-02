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

    protected null|string $date;
    protected array $userIds;

    public function __construct($users, $date = null)
    {
        $this->date = $date;
//        $this->userIds = $users->pluck('id')->toArray();
        $this->userIds = [$users->id];
    }

    public function handle()
    {
        $dateToUse = $this->date ? Carbon::parse($this->date) : Carbon::now()->subDay();
        $dateCondition = function ($query) use ($dateToUse) {
            $query->whereDate('wt.created_at', $dateToUse);
        };

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
            ->where('wt.third_party_status', true)
            ->where('wt.type', 'decrease')
            ->where($dateCondition)
            ->whereIn('wt.user_id', $this->userIds)
            ->groupBy('wt.user_id', 'u.mobile', 'u.name', 'p.national_code', 'p.address', 'p.postal_code', 'p.city', 'p.province')
            ->get()
            ->keyBy('user_id');

        $operatorData = DB::table('wallet_transactions as wt')
            ->join('operators as o', 'wt.operator_id', '=', 'o.id')
            ->join('products as p', DB::raw("JSON_UNQUOTE(JSON_EXTRACT(wt.extra_info, '$.product_id'))"), '=', 'p.id')
            ->select(
                'wt.user_id',
                'wt.operator_id',
                'o.name as operator_name',
                'o.name as operator_title',
                'wt.product_type',
                'wt.product_name',
                'p.sim_card_type',
                'wt.value as total',
                'wt.original_price as total_original_price'
            )
            ->where('wt.detail', 'decrease_purchase_buyer')
            ->where('wt.type', 'decrease')
            ->where($dateCondition)
            ->whereIn('wt.user_id', $this->userIds)
            ->whereNotNull('wt.operator_id')
            ->get()
            ->groupBy('user_id')
            ->map(function ($userItems) {
                return $userItems->groupBy('operator_id')->map(function ($items, $operatorId) {
                    $first = $items->first();

                    return [
                        'name' => $first->operator_name,
                        'title' => $first->operator_title,
                        'total' => $items->sum('total'),
                        'total_original_price' => $items->sum('total_original_price'),
                        'count' => $items->count(),
                        '_id' => (int) $operatorId,
                        'report' => $items
                            ->groupBy(function ($item) {
                                return implode('|', [
                                    trim($item->product_type ?? 'unknown_type'),
                                    trim($item->product_name ?? 'unknown_name'),
                                    trim($item->sim_card_type ?? 'unknown_sim'),
                                ]);
                            })
                            ->map(function ($groupedItems) {
                                $first = $groupedItems->first();

                                return [
                                    'total' => $groupedItems->sum('total'),
                                    'total_original_price' => $groupedItems->sum('total_original_price'),
                                    'count' => $groupedItems->count(),
                                    'product_type' => $first->product_type ?? 'unknown_type',
                                    'product_name' => $first->product_name ?? 'unknown_name',
                                    'sim_card_type' => $first->sim_card_type ?? 'unknown_sim',
                                ];
                            })
                            ->values()->toArray(),
                    ];
                });
            });

        $results = [];

        foreach ($totals as $userId => $item) {
            // Check if a report already exists for this user and date
            $existing = ReportDailyUser::where('user_id', (int)$userId)
                ->where('date', new UTCDateTime($dateToUse))
                ->first();

            if ($existing) {
                continue;
            }

            $operators = optional($operatorData->get($userId))->toArray() ?? [];
            $irancell = collect($operators)->firstWhere('name', 'Irancell');
            $mci = collect($operators)->firstWhere('name', 'Mci');
            $rightel = collect($operators)->firstWhere('name', 'Rightel');
            $aptel = collect($operators)->firstWhere('name', 'Aptel');
            $shatel = collect($operators)->firstWhere('name', 'Shatel');

            $results[] = [
                'user_id' => (int)$userId,
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
                'operators' => $operators,
                'irancell_total' => (float) ($irancell['total'] ?? 0),
                'irancell_total_original_price' => (float) ($irancell['total_original_price'] ?? 0),
                'mci_total' => (float) ($mci['total'] ?? 0),
                'mci_total_original_price' => (float) ($mci['total_original_price'] ?? 0),
                'rightel_total' => (float) ($rightel['total'] ?? 0),
                'rightel_total_original_price' => (float) ($rightel['total_original_price'] ?? 0),
                'aptel_total' => (float) ($aptel['total'] ?? 0),
                'aptel_total_original_price' => (float) ($aptel['total_original_price'] ?? 0),
                'shatel_total' => (float) ($shatel['total'] ?? 0),
                'shatel_total_original_price' => (float) ($shatel['total_original_price'] ?? 0),
                'date' => new UTCDateTime($dateToUse),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($results)) {
            ReportDailyUser::insert($results);
        }
    }
}
