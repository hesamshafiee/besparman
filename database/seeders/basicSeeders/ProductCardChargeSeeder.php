<?php

namespace Database\Seeders\basicSeeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCardChargeSeeder extends Seeder
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
                    "description" => "کارت شارژ ایرانسل - 50,000 ریالی",
                    "name" => "کارت شارژ ایرانسل - 50,000 ریالی",
                    "operator_id" => 2,
                    "period" => null,
                    "price" => "50000",
                    "profile_id" => null,
                    "second_price" => 50000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
                [
                    "description" => "کارت شارژ ایرانسل - 100,000 ریالی",
                    "name" => "کارت شارژ ایرانسل - 100,000 ریالی",
                    "operator_id" => 2,
                    "period" => null,
                    "price" => "100000",
                    "profile_id" => null,
                    "second_price" => 100000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
                [
                    "description" => "کارت شارژ ایرانسل - 200,000 ریالی",
                    "name" => "کارت شارژ ایرانسل - 200,000 ریالی",
                    "operator_id" => 2,
                    "period" => null,
                    "price" => "200000",
                    "profile_id" => null,
                    "second_price" => 200000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
                [
                    "description" => "کارت شارژ ایرانسل - 500,000 ریالی",
                    "name" => "کارت شارژ ایرانسل - 500,000 ریالی",
                    "operator_id" => 2,
                    "period" => null,
                    "price" => "500000",
                    "profile_id" => null,
                    "second_price" => 500000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
                [
                    "description" => "کارت شارژ ایرانسل - 1,000,000 ریالی",
                    "name" => "کارت شارژ ایرانسل - 1,000,000 ریالی",
                    "operator_id" => 2,
                    "period" => null,
                    "price" => "1000000",
                    "profile_id" => null,
                    "second_price" => 1000000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
                [
                    "description" => "کارت شارژ همراه اول - 50,000 ریالی",
                    "name" => "کارت شارژ همراه اول - 50,000 ریالی",
                    "operator_id" => 1,
                    "period" => null,
                    "price" => "50000",
                    "profile_id" => null,
                    "second_price" => 50000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
                [
                    "description" => "کارت شارژ همراه اول - 100,000 ریالی",
                    "name" => "کارت شارژ همراه اول - 100,000 ریالی",
                    "operator_id" => 1,
                    "period" => null,
                    "price" => "100000",
                    "profile_id" => null,
                    "second_price" => 100000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
                [
                    "description" => "کارت شارژ همراه اول - 200,000 ریالی",
                    "name" => "کارت شارژ همراه اول - 200,000 ریالی",
                    "operator_id" => 1,
                    "period" => null,
                    "price" => "200000",
                    "profile_id" => null,
                    "second_price" => 200000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
                [
                    "description" => "کارت شارژ همراه اول - 500,000 ریالی",
                    "name" => "کارت شارژ همراه اول - 500,000 ریالی",
                    "operator_id" => 1,
                    "period" => null,
                    "price" => "500000",
                    "profile_id" => null,
                    "second_price" => 500000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
                [
                    "description" => "کارت شارژ همراه اول - 1,000,000 ریالی",
                    "name" => "کارت شارژ همراه اول - 1,000,000 ریالی",
                    "operator_id" => 1,
                    "period" => null,
                    "price" => "1000000",
                    "profile_id" => null,
                    "second_price" => 1000000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
                [
                    "description" => "کارت شارژ رایتل - 20,000 ریالی",
                    "name" => "کارت شارژ رایتل - 20,000 ریالی",
                    "operator_id" => 3,
                    "period" => null,
                    "price" => "20000",
                    "profile_id" => null,
                    "second_price" => 20000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],

                [
                    "description" => "کارت شارژ رایتل - 50,000 ریالی",
                    "name" => "کارت شارژ رایتل - 50,000 ریالی",
                    "operator_id" => 3,
                    "period" => null,
                    "price" => "50000",
                    "profile_id" => null,
                    "second_price" => 50000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
                [
                    "description" => "کارت شارژ رایتل - 100,000 ریالی",
                    "name" => "کارت شارژ رایتل - 100,000 ریالی",
                    "operator_id" => 3,
                    "period" => null,
                    "price" => "100000",
                    "profile_id" => null,
                    "second_price" => 100000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
                [
                    "description" => "کارت شارژ رایتل - 200,000 ریالی",
                    "name" => "کارت شارژ رایتل - 200,000 ریالی",
                    "operator_id" => 3,
                    "period" => null,
                    "price" => "200000",
                    "profile_id" => null,
                    "second_price" => 200000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "card_charge"
                ],
                [
                    "description" => "کارت شارژ رایتل - 500,000 ریالی",
                    "name" => "کارت شارژ رایتل - 500,000 ریالی",
                    "operator_id" => 3,
                    "period" => null,
                    "price" => "500000",
                    "profile_id" => null,
                    "second_price" => 500000,
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
