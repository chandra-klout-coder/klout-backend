<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnassignedData extends Model
{
    use HasFactory;
    protected $table = 'unassigned_data';

    protected $fillable = ['user_id', 'other_id', 'value', 'type'];
}
