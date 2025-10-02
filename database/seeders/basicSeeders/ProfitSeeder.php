<?php

namespace Database\Seeders\basicSeeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        DB::table('profits')->insert(
            array(
                array('id' => '1','operator_id' => '2','type' => 'cell_internet','title' => 'بسته اینترنت ایرانسل','profit' => '3','status' => '1','created_at' => '2024-10-06 08:00:13','updated_at' => '2024-10-06 08:02:21'),
                array('id' => '2','operator_id' => '2','type' => 'td_lte_internet','title' => 'بسته اینترنت  TD-LTE ایرانسل','profit' => '3','status' => '1','created_at' => '2024-10-06 08:00:43','updated_at' => '2024-10-06 08:02:35'),
                array('id' => '3','operator_id' => '2','type' => 'cell_direct_charge','title' => 'شارژ مستقیم ایرانسل','profit' => '3','status' => '1','created_at' => '2024-10-06 08:01:08','updated_at' => '2024-10-06 11:07:35'),
                array('id' => '4','operator_id' => '1','type' => 'cell_direct_charge','title' => 'شارژ مستقیم همراه اول','profit' => '2.5','status' => '1','created_at' => '2024-10-06 08:01:42','updated_at' => '2024-10-06 11:07:01'),
                array('id' => '5','operator_id' => '1','type' => 'cell_internet','title' => 'بسته اینترنت همراه اول','profit' => '2.5','status' => '1','created_at' => '2024-10-06 08:01:56','updated_at' => '2024-10-06 11:07:20'),
                array('id' => '6','operator_id' => '3','type' => 'cell_direct_charge','title' => 'شارژ مستقیم رایتل','profit' => '3','status' => '1','created_at' => '2024-10-06 11:09:19','updated_at' => '2024-10-06 11:09:19'),
                array('id' => '7','operator_id' => '3','type' => 'cell_internet','title' => 'بسته اینترنت رایتل','profit' => '3','status' => '1','created_at' => '2024-10-06 11:10:30','updated_at' => '2024-10-06 11:10:30'),
                array('id' => '8','operator_id' => '5','type' => 'cell_direct_charge','title' => 'شارژ مستقیم شاتل موبایل','profit' => '4','status' => '1','created_at' => '2024-10-06 11:21:26','updated_at' => '2024-10-06 11:21:26'),
                array('id' => '9','operator_id' => '5','type' => 'cell_internet','title' => 'بسته اینترنت شاتل موبایل','profit' => '4','status' => '1','created_at' => '2024-10-06 11:22:02','updated_at' => '2024-10-06 11:22:02'),
                array('id' => '10','operator_id' => '4','type' => 'cell_direct_charge','title' => 'شارژ مستقیم آپتل','profit' => '4','status' => '1','created_at' => '2024-10-06 11:38:45','updated_at' => '2024-10-06 11:38:45'),
                array('id' => '11','operator_id' => '4','type' => 'cell_internet','title' => 'بسته اینترنت آپتل','profit' => '4','status' => '1','created_at' => '2024-10-06 11:39:10','updated_at' => '2024-10-06 11:39:10')
            )
        );

        DB::table('profit_splits')->insert(
            array(
                array('id' => '1','title' => 'بسته اینترنت  TD-LTE ایرانسل','profit_id' => '1','presenter_profit' => '0','seller_profit' => '1.2','created_at' => '2024-10-06 08:36:42','updated_at' => '2024-10-06 11:11:05'),
                array('id' => '2','title' => 'بسته اینترنت ایرانسل','profit_id' => '2','presenter_profit' => '0','seller_profit' => '1.2','created_at' => '2024-10-06 11:11:59','updated_at' => '2024-10-06 11:46:45'),
                array('id' => '3','title' => 'شارژ مستقیم ایرانسل','profit_id' => '3','presenter_profit' => '0','seller_profit' => '1.2','created_at' => '2024-10-06 11:18:45','updated_at' => '2024-10-06 11:18:45'),
                array('id' => '4','title' => 'شارژ مستقیم همراه اول','profit_id' => '4','presenter_profit' => '0','seller_profit' => '1','created_at' => '2024-10-06 11:19:08','updated_at' => '2024-10-06 11:19:08'),
                array('id' => '5','title' => 'بسته اینترنت همراه اول','profit_id' => '5','presenter_profit' => '0','seller_profit' => '1','created_at' => '2024-10-06 11:19:27','updated_at' => '2024-10-06 11:19:27'),
                array('id' => '6','title' => 'شارژ مستقیم رایتل','profit_id' => '6','presenter_profit' => '0','seller_profit' => '1','created_at' => '2024-10-06 11:19:41','updated_at' => '2024-10-06 11:19:41'),
                array('id' => '7','title' => 'بسته اینترنت رایتل','profit_id' => '7','presenter_profit' => '0','seller_profit' => '1','created_at' => '2024-10-06 11:19:50','updated_at' => '2024-10-06 11:19:50'),
                array('id' => '8','title' => 'شارژ مستقیم شاتل موبایل','profit_id' => '8','presenter_profit' => '0','seller_profit' => '2','created_at' => '2024-10-06 11:59:03','updated_at' => '2024-10-06 11:59:03'),
                array('id' => '9','title' => 'بسته اینترنت شاتل موبایل','profit_id' => '9','presenter_profit' => '0','seller_profit' => '2','created_at' => '2024-10-06 11:59:16','updated_at' => '2024-10-06 11:59:16'),
                array('id' => '10','title' => 'شارژ مستقیم آپتل','profit_id' => '10','presenter_profit' => '0','seller_profit' => '2','created_at' => '2024-10-06 11:59:38','updated_at' => '2024-10-06 11:59:38'),
                array('id' => '11','title' => 'بسته اینترنت آپتل','profit_id' => '11','presenter_profit' => '0','seller_profit' => '2','created_at' => '2024-10-06 11:59:49','updated_at' => '2024-10-06 11:59:49'),
                array('id' => '12','title' => 'بسته اینترنت ایرانسل','profit_id' => '1','presenter_profit' => '0','seller_profit' => '2','created_at' => '2024-10-06 12:01:23','updated_at' => '2024-10-06 12:01:23'),
                array('id' => '13','title' => 'بسته اینترنت  TD-LTE ایرانسل','profit_id' => '2','presenter_profit' => '0','seller_profit' => '2','created_at' => '2024-10-06 12:01:30','updated_at' => '2024-10-06 12:01:30'),
                array('id' => '14','title' => 'شارژ مستقیم ایرانسل','profit_id' => '3','presenter_profit' => '0','seller_profit' => '2','created_at' => '2024-10-06 12:01:37','updated_at' => '2024-10-06 12:01:37'),
                array('id' => '15','title' => 'شارژ مستقیم همراه اول','profit_id' => '4','presenter_profit' => '0','seller_profit' => '1.5','created_at' => '2024-10-06 12:02:43','updated_at' => '2024-10-06 12:02:43'),
                array('id' => '16','title' => 'بسته اینترنت همراه اول','profit_id' => '5','presenter_profit' => '0','seller_profit' => '1.5','created_at' => '2024-10-06 12:02:58','updated_at' => '2024-10-06 12:02:58'),
                array('id' => '17','title' => 'شارژ مستقیم رایتل','profit_id' => '6','presenter_profit' => '0','seller_profit' => '2','created_at' => '2024-10-06 12:03:11','updated_at' => '2024-10-06 12:03:11'),
                array('id' => '18','title' => 'بسته اینترنت رایتل','profit_id' => '7','presenter_profit' => '0','seller_profit' => '2','created_at' => '2024-10-06 12:03:19','updated_at' => '2024-10-06 12:03:19'),
                array('id' => '19','title' => 'بسته اینترنت ایرانسل','profit_id' => '1','presenter_profit' => '0','seller_profit' => '2.9','created_at' => '2024-10-06 12:24:42','updated_at' => '2024-10-06 12:24:42'),
                array('id' => '20','title' => 'بسته اینترنت  TD-LTE ایرانسل','profit_id' => '2','presenter_profit' => '0','seller_profit' => '2.9','created_at' => '2024-10-06 12:24:53','updated_at' => '2024-10-06 12:24:53'),
                array('id' => '21','title' => 'شارژ مستقیم ایرانسل','profit_id' => '3','presenter_profit' => '0','seller_profit' => '2.9','created_at' => '2024-10-06 12:25:02','updated_at' => '2024-10-06 12:25:02'),
                array('id' => '22','title' => 'بسته اینترنت ایرانسل','profit_id' => '1','presenter_profit' => '0','seller_profit' => '2.8','created_at' => '2024-10-06 12:25:16','updated_at' => '2024-10-06 12:25:16'),
                array('id' => '23','title' => 'بسته اینترنت  TD-LTE ایرانسل','profit_id' => '2','presenter_profit' => '0','seller_profit' => '2.8','created_at' => '2024-10-06 12:25:24','updated_at' => '2024-10-06 12:25:24'),
                array('id' => '24','title' => 'شارژ مستقیم ایرانسل','profit_id' => '3','presenter_profit' => '0','seller_profit' => '2.8','created_at' => '2024-10-06 12:25:31','updated_at' => '2024-10-06 12:25:31'),
                array('id' => '25','title' => 'شارژ مستقیم رایتل','profit_id' => '6','presenter_profit' => '0','seller_profit' => '2.8','created_at' => '2024-10-06 18:49:01','updated_at' => '2024-10-06 18:49:01'),
                array('id' => '26','title' => 'بسته اینترنت رایتل','profit_id' => '7','presenter_profit' => '0','seller_profit' => '2.8','created_at' => '2024-10-06 18:49:17','updated_at' => '2024-10-06 18:49:17'),
                array('id' => '27','title' => 'شارژ مستقیم شاتل موبایل','profit_id' => '8','presenter_profit' => '0','seller_profit' => '3.8','created_at' => '2024-10-06 18:50:31','updated_at' => '2024-10-06 18:50:31'),
                array('id' => '28','title' => 'بسته اینترنت شاتل موبایل','profit_id' => '9','presenter_profit' => '0','seller_profit' => '3.8','created_at' => '2024-10-06 18:50:42','updated_at' => '2024-10-06 18:50:42')
            )
        );

        DB::table('profit_groups')->insert(
            array(
                array('id' => '2','title' => 'Esaj1','profit_split_ids' => '[1,3,2,4,5,6,7,8,9,10,11]','created_at' => '2024-10-06 12:31:44','updated_at' => '2024-10-06 12:31:44'),
                array('id' => '3','title' => 'Esaj2','profit_split_ids' => '[12,13,14,15,16,17,18,8,9,10,11]','created_at' => '2024-10-06 12:52:01','updated_at' => '2024-10-06 12:52:01'),
                array('id' => '4','title' => 'وب سرویس 2.9 درصد','profit_split_ids' => '[19,20,21]','created_at' => '2024-10-06 12:55:05','updated_at' => '2024-10-06 12:55:05'),
                array('id' => '5','title' => 'وب سرویس 2.8 درصد','profit_split_ids' => '[22,23,24,25,26,27,28]','created_at' => '2024-10-06 18:53:40','updated_at' => '2024-10-06 18:53:40')
            )
        );
    }
}
