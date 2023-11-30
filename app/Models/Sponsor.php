<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sponsor extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'user_id',
        'event_id',
        'first_name',
        'last_name',
        'job_title',
        'company',
        'industry',
        'file',
        'official_email',
        'phone_number',
        'country',
        'city',
        'website',
        'employee_size',
        'linkedin_page_link',
        'sponsorship_package',
        'amount',
        'status',
        'brand_name',
        'company_name',
        'job_title_name'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }
}
