<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FeedBack extends Model
{
    use HasFactory;
    protected $table = "feedbacks";

    protected $fillable = [
        'uuid',
        'event_id',
        'attendee_id',
        'subject',
        'message',
        'rating',
    ];
}
