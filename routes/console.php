<?php

use App\Jobs\DailyUsersBalanceJob;
use App\Models\IdempotencyKey;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

app(Schedule::class)->command('activitylog:clean')->weekly();

app(Schedule::class)->command('payments:settle-mellat')->dailyAt('23:45');

app(Schedule::class)->call(function () {
    User::chunk(50, function ($users) {
        DailyUsersBalanceJob::dispatch($users)->onQueue('report');
    });

    DB::table('personal_access_tokens')
        ->where('expires_at', '<', now())
        ->delete();
})->dailyAt('00:01');

app(Schedule::class)->call(function () {
    IdempotencyKey::where('expires_at', '<', now())->delete();
})->dailyAt('03:00');

