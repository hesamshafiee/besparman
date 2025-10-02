<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Permission\Models\Role;

class SuperAdminJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = User::where('mobile', config('app.mobile_number_test_1'))->first();
        $user2 = User::where('mobile', User::MOBILE_ESAJ)->first();

        if ($user && env('APP_ENV') !== 'production') {
            $role = Role::where('name', 'super-admin')->first();
            $user->assignRole($role);
            $user2->assignRole($role);
        }
    }
}
