<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class sendEventCancelledEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $message;
    // protected $data;

    public function __construct($subject, $message)
    {
        $this->subject = $subject;
        $this->message = $message;

        // $data = [
        //     'attendee' => $this->message['attendee_details'],
        //     'event' => $this->message['event_details']
        // ];

        // $this->data = $data;
    }

    public function build()
    {
        return $this->markdown('emails.attendee-invitation-cancelled')->with(['message' => $this->message]);

        // return $this->subject($this->subject)
        //     ->view('emails.attendee-invitation-cancelled')
        //     ->with('data', $this->message);

            // ->with('data', $this->data);

    }

}
