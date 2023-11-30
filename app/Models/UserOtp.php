<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOtp extends Model
{
    use HasFactory;

    
    protected $table = 'user_otps'; 
    
    public $timestamps = false;

    protected $fillable = [
        'email', 'email_otp', 'mobile', 'mobile_otp'
    ];
}
