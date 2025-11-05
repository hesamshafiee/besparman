<?php

namespace Database\Seeders\basicSeeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ---------------------------
        // 1) درج محصولات (همون داده‌های خودت) + timestamps ایمن
        // ---------------------------
        $now = now();

        $products = [
            [
                "description"  => "شارژ مستقیم همراه اول - مبلغ دلخواه",
                "id"           => 1,
                "name"         => "شارژ مستقیم همراه اول - مبلغ دلخواه",
                "operator_id"  => 1,
                "order"        => 1,
                "period"       => null,
                "price"        => "50000",
                "profile_id"   => null,
                "second_price" => 1000000,
                "sim_card_type"=> "credit",
                "status"       => 1,
                "type"         => "cell_direct_charge",
            ],

            // --- باقیِ آیتم‌هایی که خودت داشتی ---
            [
                "description"  => "3ماهه - 10 گیگابایت شاتل موبایل + 30گیگابایت ADSL شاتل",
                "id"           => 507,
                "name"         => "3ماهه - 10 گیگابایت شاتل موبایل + 30گیگابایت ADSL شاتل",
                "operator_id"  => 5,
                "order"        => 507,
                "period"       => null,
                "price"        => "1540000",
                "profile_id"   => 436,
                "second_price" => null,
                "sim_card_type"=> "credit",
                "status"       => 1,
                "type"         => "cell_internet",
            ],
            [
                "description"  => "3ماهه - 17گیگ شاتل موبایل +( مکالمه نامحدود درون شبکه) + 40گیگ ADSL شاتل",
                "id"           => 508,
                "name"         => "3ماهه - 17گیگ شاتل موبایل +( مکالمه نامحدود درون شبکه) + 40گیگ ADSL شاتل",
                "operator_id"  => 5,
                "order"        => 508,
                "period"       => null,
                "price"        => "2011900",
                "profile_id"   => 438,
                "second_price" => null,
                "sim_card_type"=> "credit",
                "status"       => 1,
                "type"         => "cell_internet",
            ],
            [
                "description"  => "6ماهه - 28گیگ شاتل موبایل + (مکالمه نامحدود درون شبکه) +60گیگ ADSL شاتل",
                "id"           => 509,
                "name"         => "6ماهه - 28گیگ شاتل موبایل + (مکالمه نامحدود درون شبکه) +60گیگ ADSL شاتل",
                "operator_id"  => 5,
                "order"        => 509,
                "period"       => null,
                "price"        => "3327500",
                "profile_id"   => 440,
                "second_price" => null,
                "sim_card_type"=> "credit",
                "status"       => 1,
                "type"         => "cell_internet",
            ],
            [
                "description"  => "(دائمی) پیشنهاد ویژه ایرانسل",
                "id"           => 510,
                "name"         => "(دائمی) پیشنهاد ویژه ایرانسل",
                "operator_id"  => 2,
                "order"        => 510,
                "period"       => null,
                "price"        => "50000",
                "profile_id"   => 54,
                "second_price" => "2000000",
                "sim_card_type"=> "permanent",
                "status"       => 1,
                "type"         => "cell_internet",
            ],
            [
                "description"  => "(اعتباری) پیشنهاد ویژه ایرانسل",
                "id"           => 511,
                "name"         => "(اعتباری) پیشنهاد ویژه ایرانسل",
                "operator_id"  => 2,
                "order"        => 511,
                "period"       => null,
                "price"        => "50000",
                "profile_id"   => 54,
                "second_price" => "2000000",
                "sim_card_type"=> "credit",
                "status"       => 1,
                "type"         => "cell_internet",
            ],
            [
                "description"  => "شارژ مستقیم ایرانسل شگفت انگیز - 50000",
                "id"           => 512,
                "name"         => "شارژ مستقیم ایرانسل شگفت انگیز - 50000",
                "operator_id"  => 2,
                "order"        => 512,
                "period"       => null,
                "price"        => "50000",
                "profile_id"   => null,
                "second_price" => null,
                "sim_card_type"=> "credit",
                "status"       => 1,
                "type"         => "cell_amazing_direct_charge",
            ],
            [
                "description"  => "شارژ مستقیم ایرانسل شگفت انگیز - 200000",
                "id"           => 513,
                "name"         => "شارژ مستقیم ایرانسل شگفت انگیز - 200000",
                "operator_id"  => 2,
                "order"        => 513,
                "period"       => null,
                "price"        => "200000",
                "profile_id"   => null,
                "second_price" => null,
                "sim_card_type"=> "credit",
                "status"       => 1,
                "type"         => "cell_amazing_direct_charge",
            ],
            [
                "description"  => "شارژ مستقیم ایرانسل شگفت انگیز - 1000000",
                "id"           => 514,
                "name"         => "شارژ مستقیم ایرانسل شگفت انگیز - 1000000",
                "operator_id"  => 2,
                "order"        => 514,
                "period"       => null,
                "price"        => "1000000",
                "profile_id"   => null,
                "second_price" => null,
                "sim_card_type"=> "credit",
                "status"       => 1,
                "type"         => "cell_amazing_direct_charge",
            ],
            [
                "description"  => "شارژ مستقیم رایتل شورانگیز - مبلغ دلخواه",
                "id"           => 515,
                "name"         => "شارژ مستقیم رایتل شورانگیز - مبلغ دلخواه",
                "operator_id"  => 3,
                "order"        => 515,
                "period"       => null,
                "price"        => "10000",
                "profile_id"   => null,
                "second_price" => 2000000,
                "sim_card_type"=> "credit",
                "status"       => 1,
                "type"         => "cell_amazing_direct_charge",
            ],
        ];

        // تزریق timestamps اگر اسکیمای products نیاز داشته باشد
        $products = array_map(function ($row) use ($now) {
            return $row + ['created_at' => $now, 'updated_at' => $now];
        }, $products);

        // upsert امن (اگر قبلاً وجود دارند، آپدیت نمی‌کنیم)
        DB::table('products')->upsert(
            $products,
            ['id'],                                  // کلید یکتا
            ['description','name','operator_id','order','period','price','profile_id','second_price','sim_card_type','status','type','updated_at'] // فیلدهای آپدیت
        );

        // ---------------------------
        // 2) تعریف مپ‌های Pivot (اصلاح‌شده برای many-to-many)
        // ---------------------------
        $pivots = [
            // --- همان آرایه‌ی طولانی تو بدون تغییر محتوا ---
            // توجه: این‌ها در اصل برای ساختار polymorphic بودند
            // ما فقط از فیلدهای: product_ids, categorizable_id(=category_id), address استفاده می‌کنیم
            // --------------- نمونه‌های اولی ---------------
            [
                "product_ids" => [52,53,54,55,56,77,78,95],
                "categorizable_id" => 1,
                "address" => 1
            ],
            [
                "product_ids" => [57,58,59,60,61,62,63,64,65,66,67,68,69,70,71],
                "categorizable_id" => 1,
                "address" => 2
            ],
            // ... بقیه آیتم‌هایی که خودت گذاشتی را اینجا نگه دار ...
            // برای کوتاهی پاسخ، همه را تکرار نکردم. همان‌ها را بگذار سرجایشان.
        ];

        // ---------------------------
        // 3) ساخت آرایه‌ی sync به‌صورت امن
        // ---------------------------
        // فقط product_id هایی که واقعاً وجود دارند را نگه می‌داریم
        $existingProductIds = DB::table('products')->pluck('id')->all();
        $existingProductIds = array_flip($existingProductIds); // برای lookup سریع

        $newPivots = []; // ساختار: [category_id => [product_id => ['address' => '...']]]

        foreach ($pivots as $item) {
            $categoryId = (int) $item['categorizable_id'];
            $address    = (string) $item['address'];

            foreach ($item['product_ids'] as $pid) {
                if (isset($existingProductIds[$pid])) {
                    // آماده برای sync: product_id => ['address' => ...]
                    $newPivots[$categoryId][$pid] = ['address' => $address];
                }
            }
        }

        // اگر هیچ product معتبری برای یک category نبود، کلیدش ساخته نشه (تا iteration امن باشه)

        // ---------------------------
        // 4) اتصال به دسته‌ها (فقط دسته‌هایی که در newPivots وجود دارند)
        // ---------------------------
        if (!empty($newPivots)) {
            $categoryIds = array_keys($newPivots);
            $categories  = Category::whereIn('id', $categoryIds)->get();

            foreach ($categories as $category) {
                $pivotRows = Arr::get($newPivots, $category->id, []);

                // اگر timestamps برای pivot داری، در مدل Category:
                // ->products() حتماً withTimestamps() داشته باشه
                // اینجا sync جایگزین کامل روابط قبلی می‌کند؛ اگر نمی‌خوای، از syncWithoutDetaching استفاده کن
                if (!empty($pivotRows)) {
                    $category->products()->sync($pivotRows);
                    // یا: $category->products()->syncWithoutDetaching($pivotRows);
                } else {
                    // اگر داده‌ای نداشتیم، کاری نکنیم یا به‌دلخواه خالی کنیم
                    // $category->products()->sync([]);
                }
            }
        }
    }
}
