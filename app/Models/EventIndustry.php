<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventIndustry extends Model
{
    use HasFactory;
    protected $table = 'event_industry';

    protected $fillable = [
        'event_id',
        'industry_id'
    ];
}
