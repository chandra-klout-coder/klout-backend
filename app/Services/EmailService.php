<?php

namespace App\Services;

class EmailService
{
    //For Registration OTP Verification
    public function sendRegistrationEmail($to, $subject, $message)
    {
        \Illuminate\Support\Facades\Mail::to($to)->send(new \App\Mail\RegistrationInvitation($subject, $message));
    }

    public function sendEmail($to, $subject, $message)
    {
        \Illuminate\Support\Facades\Mail::to($to)->send(new \App\Mail\AttendeeInvitation($subject, $message));
    }

    //Changed Password Successful Message
    public function sendChangedPasswordEmail($to, $subject, $message)
    {
        \Illuminate\Support\Facades\Mail::to($to)->send(new \App\Mail\sendChangedPasswordEmail($subject, $message));
    }

    public function sendEventCancelledEmail($to, $subject, $message)
    {
        \Illuminate\Support\Facades\Mail::to($to)->send(new \App\Mail\sendEventCancelledEmail($subject, $message));
    }
    
}