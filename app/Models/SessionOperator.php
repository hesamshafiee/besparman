<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionOperator extends Model
{
    use HasFactory;

    public static function getSession_id($operator, $seconds = 7200)
    {
        $sessionTime = time() - $seconds;
        $sessionTime = date("Y-m-d H:i:s", $sessionTime);

        $sessionOperator = SessionOperator::where('operator', $operator)
            ->where('created_at', '>', $sessionTime)
            ->first();

        if ($sessionOperator) {
            return $sessionOperator->session;
        } else {
            return '';
        }
    }
}
