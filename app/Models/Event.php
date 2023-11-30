<?php

namespace App\Models;

use App\Models\Attendee;
use App\Models\Notification;
use App\Models\SmsNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'title',
        'description',
        'event_date',
        'location',
        'event_start_date',
        'event_end_date',
        'start_time',
        'start_minte_time',
        'start_time_type',
        'end_time',
        'end_minte_time',
        'end_time_type',
        'image',
        'event_venue',
        'event_venue_name',
        'event_venue_address_1',
        'event_venue_address_2',
        'city',
        'state',
        'country',
        'pincode',
        'qr_code',
        'feedback',
        'status'
    ];
    
    public function attendees()
    {
        return $this->hasMany(Attendee::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'event_id');
    }

    public function smsnotifications()
    {
        return $this->hasMany(SmsNotification::class, 'event_id');
    }
}
