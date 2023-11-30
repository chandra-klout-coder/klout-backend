<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    public $incrementing = true;


    protected $keyType = 'string';
    protected $primaryKey = 'id';
    
    protected $fillable = ['name']; 
}
