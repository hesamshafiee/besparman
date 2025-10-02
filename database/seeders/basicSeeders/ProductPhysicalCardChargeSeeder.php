<?php

namespace Database\Seeders\basicSeeders;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductPhysicalCardChargeSeeder extends Seeder
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
                "description" => "کارت شارژ فیزیکی ایرانسل - 20,000 ریالی",
                "name" => "کارت شارژ فیزیکی ایرانسل - 20,000 ریالی",
                "operator_id" => 2,
                "period" => null,
                "price" => "20000",
                "profile_id" => null,
                "second_price" => 20000,
                "sim_card_type" => "credit",
                "status" => 1,
                "type" => "physical_card_charge"
            ],
            [
                "description" => "کارت شارژ فیزیکی ایرانسل - 200,000 ریالی",
                "name" => "کارت شارژ فیزیکی ایرانسل - 200,000 ریالی",
                "operator_id" => 2,
                "period" => null,
                "price" => "200000",
                "profile_id" => null,
                "second_price" => 200000,
                "sim_card_type" => "credit",
                "status" => 1,
                "type" => "physical_card_charge"
            ],
            [
                "description" => "کارت شارژ فیزیکی همراه اول - 20,000 ریالی",
                "name" => "کارت شارژ فیزیکی همراه اول - 20,000 ریالی",
                "operator_id" => 1,
                "period" => null,
                "price" => "20000",
                "profile_id" => null,
                "second_price" => 20000,
                "sim_card_type" => "credit",
                "status" => 1,
                "type" => "physical_card_charge"
            ],
            [
                "description" => "کارت شارژ فیزیکی همراه اول - 50,000 ریالی",
                "name" => "کارت شارژ فیزیکی همراه اول - 50,000 ریالی",
                "operator_id" => 1,
                "period" => null,
                "price" => "50000",
                "profile_id" => null,
                "second_price" => 50000,
                "sim_card_type" => "credit",
                "status" => 1,
                "type" => "physical_card_charge"
            ],
            [
                    "description" => "کارت شارژ فیزیکی همراه اول - 200,000 ریالی",
                    "name" => "کارت شارژ فیزیکی همراه اول - 200,000 ریالی",
                    "operator_id" => 1,
                    "period" => null,
                    "price" => "200000",
                    "profile_id" => null,
                    "second_price" => 200000,
                    "sim_card_type" => "credit",
                    "status" => 1,
                    "type" => "physical_card_charge"
                ],
        ];

        foreach ($products as $index => &$product) {
            $product['id'] = $lastId + $index + 1;
            $product['order'] = $lastId + $index + 1;
        }

        DB::table('products')->insert($products);

        $newProducts = Product::where('type', 'physical_physical_card_charge')->get();

        foreach ($newProducts as $product) {
            Warehouse::create([
                'product_id' => $product->id,
                'count' => 100,
                'price' => $product->price,
            ]);
        }
    }
}
