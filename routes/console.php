<?php

use App\Jobs\DailyUsersBalanceJob;
use App\Jobs\UserReportJob;
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

app(Schedule::class)->command('topupgroup:process')->everyMinute()->withoutOverlapping();
app(Schedule::class)->command('app:reconciliation')->everyTenMinutes();
app(Schedule::class)->command('telescope:prune --hours=48')->daily();
app(Schedule::class)->command('payments:settle-mellat')->dailyAt('23:45');
app(Schedule::class)->command('report:operators-remaining')->dailyAt('00:03');

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

app(Schedule::class)->call(function () {
    User::chunk(50, function ($users) {
        foreach ($users as $user) {
            UserReportJob::dispatch($user)->onQueue('report');
        }
    });
})->dailyAt('00:15');
