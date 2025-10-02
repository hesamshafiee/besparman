<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;

trait LogsActivityWithRequest
{
    use LogsActivity;

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @param Activity $activity
     * @param string $eventName
     * @return void
     */
    public function tapActivity(Activity $activity, string $eventName): void
    {
        $requestData = app()->runningInConsole()
            ? ['source' => 'console']
            : [
                'ip' => request()->ip(),
                'path' => request()->path(),
                'user_agent' => request()->userAgent(),
                'method' => request()->method(),
            ];

        // Handle both array and Collection cases
        if (is_array($activity->properties)) {
            $activity->properties = array_merge($activity->properties, $requestData);
        } else {
            $activity->properties = $activity->properties->merge($requestData);
        }
    }
}
