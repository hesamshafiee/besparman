<?php

namespace Database\Seeders\basicSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfitCardChargeSeeder extends Seeder
{
    public function run(): void
    {
        $lastProfitId = DB::table('profits')->max('id') ?? 0;
        $lastSplitId = DB::table('profit_splits')->max('id') ?? 0;

        $profits = [
            ['operator_id' => '2','type' => 'card_charge','title' => 'کارت شارژ ایرانسل','profit' => '3','status' => '1','created_at' => now(),'updated_at' => now()],
            ['operator_id' => '1','type' => 'card_charge','title' => 'کارت شارژ همراه اول','profit' => '2.5','status' => '1','created_at' => now(),'updated_at' => now()],
            ['operator_id' => '3','type' => 'card_charge','title' => 'کارت شارژ رایتل','profit' => '3','status' => '1','created_at' => now(),'updated_at' => now()],
        ];

        foreach ($profits as $i => &$profit) {
            $profit['id'] = $lastProfitId + $i + 1;
        }
        DB::table('profits')->insert($profits);

        $profitIds = array_column($profits, 'id', 'title');

        // دیتای profit_splits
        $splits = [
            ['title' => 'کارت شارژ ایرانسل','profit_id' => $profitIds['کارت شارژ ایرانسل'],'presenter_profit' => '0','seller_profit' => '1','created_at' => now(),'updated_at' => now()],
            ['title' => 'کارت شارژ همراه اول','profit_id' => $profitIds['کارت شارژ همراه اول'],'presenter_profit' => '0','seller_profit' => '1','created_at' => now(),'updated_at' => now()],
            ['title' => 'کارت شارژ رایتل','profit_id' => $profitIds['کارت شارژ رایتل'],'presenter_profit' => '0','seller_profit' => '1','created_at' => now(),'updated_at' => now()],
        ];

        foreach ($splits as $i => &$split) {
            $split['id'] = $lastSplitId + $i + 1;
        }
        DB::table('profit_splits')->insert($splits);

        // گرفتن idهای جدید split برای آپدیت profit_groups
        $newSplitIds = array_column($splits, 'id', 'title');

        // لیست گروه‌هایی که باید آپدیت شوند
        $groupIds = [2, 3, 4, 5];

        foreach ($groupIds as $groupId) {
            $group = DB::table('profit_groups')->where('id', $groupId)->first();
            if (!$group) continue;

            $existingIds = json_decode($group->profit_split_ids, true) ?? [];

            // ترکیب آی‌دی‌های قبلی با جدید
            $updatedIds = array_merge($existingIds, array_values($newSplitIds));

            // حذف تکراری‌ها و ذخیره
            DB::table('profit_groups')->where('id', $groupId)->update([
                'profit_split_ids' => json_encode(array_values(array_unique($updatedIds)))
            ]);
        }
    }
}
