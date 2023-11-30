<?php

namespace App\Services;

use Twilio\Rest\Client;

class SmsServices
{
    protected $twilio;
    protected $twilioPhoneNumber;

    public function __construct()
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $this->twilioPhoneNumber = env('TWILIO_PHONE_NUMBER');

        $this->twilio = new Client($sid, $token);
    }

    public function sendSMS($to, $message)
    {
        try {

            $this->twilio->messages->create($to, [
                'from' => $this->twilioPhoneNumber,
                'body' => $message,
            ]);

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }
}
