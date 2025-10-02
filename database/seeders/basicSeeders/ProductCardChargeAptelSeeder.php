<?php

namespace Database\Seeders\basicSeeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCardChargeAptelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {

        $lastId = Product::max('id') ?? 0;

        $products = [
                [
                    "description" => "کارت شارژ آپتل - 20,000 ریالی",
                    "name" => "کارت شارژ آپتل - 20,000 ریالی",
                    "operator_id" => 4,
                    "period" => null,
                    "price" => "20000",
                    "profile_id" => null,
                    "second_price" => 20000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
                [
                    "description" => "کارت شارژ آپتل - 50,000 ریالی",
                    "name" => "کارت شارژ آپتل - 50,000 ریالی",
                    "operator_id" => 4,
                    "period" => null,
                    "price" => "50000",
                    "profile_id" => null,
                    "second_price" => 50000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
                [
                    "description" => "کارت شارژ آپتل - 100,000 ریالی",
                    "name" => "کارت شارژ آپتل - 100,000 ریالی",
                    "operator_id" => 4,
                    "period" => null,
                    "price" => "100000",
                    "profile_id" => null,
                    "second_price" => 100000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
                [
                    "description" => "کارت شارژ آپتل - 200,000 ریالی",
                    "name" => "کارت شارژ آپتل - 200,000 ریالی",
                    "operator_id" => 4,
                    "period" => null,
                    "price" => "200000",
                    "profile_id" => null,
                    "second_price" => 200000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
        ];


        foreach ($products as $index => &$product) {
            $product['id'] = $lastId + $index + 1;
            $product['order'] = $lastId + $index + 1;
        }

        DB::table('products')->insert($products);

    }
}
