<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PanelMessage extends Model
{
    use HasFactory;
    use LogsActivityWithRequest;


    protected $fillable = ['title', 'short_content', 'body', 'status', 'is_open'];
}
