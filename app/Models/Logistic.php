<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logistic extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;

    protected $fillable = [
        'city',
        'province',
        'country',
        'type',
        'capacity',
        'price',
        'min_price_for_free_delivery',
        'start_delivery_after_day',
        'start_delivery_after_time',
        'start_time',
        'end_time',
        'divide_time',
        'is_active_in_holiday',
        'days_not_working',
        'status',
        'default',
        'is_capital',
        'description',
        ];

    /**
     * @param int|null $start
     * @param int|null $end
     * @return bool
     */
    public function checkTime(?int $start, ?int $end): bool
    {
        if ($start === null && $end === null) {
            return true;
        }
        $startMinutes   = $start * 60;
        $endMinutes     = $end * 60;
        $workingStart   = $this->start_time * 60;
        $workingEnd     = $this->end_time * 60;
        $divide_time     = $this->divide_time * 60;

        if (($endMinutes - $startMinutes) !== $divide_time) {
            return false;
        }

        if ($startMinutes < $workingStart || $endMinutes > $workingEnd) {
            return false;
        }
        return (($startMinutes - $workingStart) % $divide_time) === 0;
    }
}
