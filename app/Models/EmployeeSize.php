<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeSize extends Model
{
    use HasFactory;
    protected $fillable = [
        'size',
        'detail'
    ];
}
