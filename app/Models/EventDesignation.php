<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventDesignation extends Model
{
    use HasFactory;
    protected $table = 'event_designation';

    protected $fillable = [
        'event_id',
        'designation_id'
    ];
}
