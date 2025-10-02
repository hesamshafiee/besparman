<?php

namespace App\Jobs;

use App\Models\WalletTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseTransferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $point_festival_user=DB::connection('mysql_old')->table('tbl_point_festival_user')
            ->where('festival_id', '=', 4)
            ->get();
        $point_festival_userArray = [] ;
        foreach ($point_festival_user as $id=>$value){
            $point_festival_userArray[$value->user_id] = floor($value->sum_point - $value->spent_point) ;
        }


        $cities=DB::connection('mysql_old')->table('tbl_city')
            ->get();
        $provinces=DB::connection('mysql_old')->table('tbl_province')->get();
        $provincesArray = [] ;

        foreach ($provinces as $id=>$value){
            $provincesArray[$value->id] = $value->name ;
        }

        $citiesArray = [] ;
        foreach ($cities as $id=>$value){
            $citiesArray[$value->id]['city_name'] = $value->name ;
            $citiesArray[$value->id]['province_name'] = $provincesArray[$value->province_id] ;
        }

        $test = DB::connection('mysql_old')->table('tbl_user')

            ->join('final_esaj.tbl_user_profile as db2','tbl_user.id','=','db2.user_id')
            ->where([
                ['role_id', '=', 61],
                ['logical_delete','=','0']

            ])
            ->orderBy('id')
            ->chunk(100, function ($courses) use ($citiesArray, $point_festival_userArray) {
                $counter = isset(DB::table('profiles')->latest('id')->first()->id) ? (DB::table('profiles')->latest('id')->first()->id)+1 : 1000 ;


                foreach ($courses as $item) {
                    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
                    $string =$item->mobile;
                    $num = range(0, 9);
                    $convertedPersianNums = str_replace($persian, $num, $string);

                    $mobile = '98'.substr($convertedPersianNums, 1, 10); ;


                    $profit_group_id = 2 ;
                    $profit_groupablesAdd = false ;
                    if($item->tariff_id == 8){
                        $profit_groupablesAdd = true ;
                        $profit_group_id = 2 ;
                    }elseif ($item->tariff_id == 6){
                        $profit_groupablesAdd = true ;
                        $profit_group_id = 3 ;
                    }elseif ($item->tariff_id == 5){
                        $profit_groupablesAdd = true ;
                        $profit_group_id = 2 ;
                    }

                    if($profit_groupablesAdd){
                        $profit_groupablesArray[] = [
                            'profit_group_id' => $profit_group_id,
                            'profit_groupable_id'=>$item->id,
                            'profit_groupable_type'=>'App\Models\User'
                        ] ;
                    }

                    $usersArray[] = [
                        'id' => $item->id,
                        'profile_id' => $item->id,
                        'name' => $item->fname.' '.$item->lname,
                        'mobile' => $mobile,
                        'type' => 'panel',
                        'points' => isset($point_festival_userArray[$item->user_id]) ?$point_festival_userArray[$item->user_id]:0,
                        'presenter_code' => Str::random(7),
                        'deleted_at' => '2012-12-12 12:12:12',
                        'created_at' => now(),
                        'updated_at' => now(),
                        'profile_confirm' => now(),
                    ];
                    $birtday = '2000-01-01';
                    if($item->birth_date !='0000-00-00')
                        $birtday = $item->birth_date;

                    $profilesArray[] = [
                        'id' => $item->id,
                        'birth_date' => $birtday,
                        'address' => $item->address,
                        'national_code' => $item->national_code,
                        'postal_code' => $item->postal_code,
                        'education' => $item->postal_code,
                        'store_name' => $item->shop_name,
                        'province' => $citiesArray[$item->city_id]['province_name'],
                        'city' => $citiesArray[$item->city_id]['city_name'],
                        'gender' => 'male',
                        'phone' => $item->tel,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $usernamesArray[] = [
                        'phone' => $mobile,
                        'username' => $item->username,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];


                    $walletsArray[] = [
                        'id' => $counter,
                        'value' => number_format($item->remaining_credit, 4, '.', '') ,
                        'user_id' => $item->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];




                    $wallet_transactionsArray[] = [
                        'wallet_id' => $counter,
                        'wallet_value_after_transaction' => number_format($item->remaining_credit, 4, '.', ''),
                        'type' => 'increase',
                        'resnumber' => time() . mt_rand(100000, 999999) . mt_rand(1000, 9999),
                        'value' =>  number_format($item->remaining_credit, 4, '.', ''),
                        'status' => 'confirmed',
                        'detail' => WalletTransaction::DETAIL_INCREASE_ADMIN, //por beshe
                        'user_id' => $item->id,
                        'province' => $citiesArray[$item->city_id]['province_name'],
                        'city' => $citiesArray[$item->city_id]['city_name'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $counter++ ;
                }

                DB::connection('mysql')->table('profiles')->insert($profilesArray);
                DB::connection('mysql')->table('users')->insert($usersArray);
                DB::connection('mysql')->table('usernames')->insert($usernamesArray);
                DB::connection('mysql')->table('wallets')->insert($walletsArray);
                DB::connection('mysql')->table('wallet_transactions')->insert($wallet_transactionsArray);
                DB::connection('mysql')->table('profit_groupables')->insert($profit_groupablesArray);
            });


//        DB::connection('mysql_old')->table('tbl_contact')
//
//            ->orderBy('id')
//            ->chunk(500, function ($courses) {
//
//                foreach ($courses as $item) {
//                    $cntArray[] = [
//                        'user_id' => $item->user_id,
//                        'phone_number' => '98'.substr($item->number, 1, 10),
//                        'name' => $item->name,
//                        'last_settings' => '{}',
//                        'created_at' => now(),
//                        'updated_at' => now(),
//                    ];
//
//                }
//
//                DB::connection('mysql')->table('phone_books')->insertOrIgnore($cntArray);
//
//            });
    }
}
