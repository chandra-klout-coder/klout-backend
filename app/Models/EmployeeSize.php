<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSize extends Model
{
    use HasFactory;
    protected $fillable = [
        'size',
        'dertail'
    ];
}
