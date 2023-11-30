<?php

namespace App\Models;

use App\Models\Event;
use App\Models\FeedBack;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'event_id',
        'first_name',
        'last_name',
        'image',
        'virtual_business_card',
        'job_title',
        'company_name',
        'industry',
        'email_id',
        'phone_number',
        'alternate_mobile_number',
        'website',
        'linkedin_page_link',
        'employee_size',
        'company_turn_over',
        'status'
    ];

    public function feedbacks()
    {
        return $this->hasMany(FeedBack::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
