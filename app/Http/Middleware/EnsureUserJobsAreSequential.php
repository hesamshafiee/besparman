<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Cache;

class EnsureUserJobsAreSequential
{
    public function handle($job, $next)
    {
        $userId = $job->userMobile ?? null;

        if (!$userId) {
            return $next($job);
        }

        $lockKey = "user-job-lock:{$userId}";
        $lock = Cache::lock($lockKey, 5);

        if ($lock->get()) {
            try {
                $next($job);
            } finally {
                $lock->release();
            }
        } else {
            $job->release(rand(2, 6));
        }
    }
}
