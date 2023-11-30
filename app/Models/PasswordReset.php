<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PasswordReset extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'email', 'token'
    ];

    // Define the relationship between the password reset token and the user
    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }
}
