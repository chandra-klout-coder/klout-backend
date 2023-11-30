<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AttendeeInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $message;
    protected $data;

    public function __construct($subject, $message)
    {
        $this->subject = $subject;
        $this->message = $message;

        $data = [
            'attendee' => $this->message['attendee_details'],
            'event' => $this->message['event_details']
        ];

        $this->data = $data;
    }

    public function build()
    {
        return $this->subject($this->subject)
            ->view('emails.attendee-invitation')
            ->with('data', $this->data);
    }
}
