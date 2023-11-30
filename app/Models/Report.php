<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'event_id',
        'event_date',
        'report_name',
        'event_tags',
        'event_attribute',
        'status'
    ];
}
