<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_id',
        'send_to',
        'send_method',
        'subject',
        'message',
        'start_date',
        'start_date_time',
        'start_date_type',
        'end_date',
        'end_date_time',
        'end_date_type',
        'no_of_times',
        'hour_interval',
        'status'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
