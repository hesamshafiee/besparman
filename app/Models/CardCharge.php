<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardCharge extends Model
{
    use LogsActivityWithRequest;
    const STATUS_OPEN = 'open';
    const STATUS_SOLD = 'sold';
    const STATUS_PENDINING = 'pending';



    protected $fillable = [ 'file_name'];



    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return  $this->belongsTo(User::class);
    }




}
