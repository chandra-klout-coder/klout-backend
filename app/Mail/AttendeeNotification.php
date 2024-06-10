<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AttendeeNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $attendee;
    public $subject;
    public $message;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($attendee, $subject, $message)
    {
        $this->attendee = $attendee;
        $this->subject = $subject;
        $this->message = $message;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subject)
                    ->view('emails.attendee_notification')
                    ->with([
                        'attendee' => $this->attendee,
                        'messageContent' => $this->message
                    ]);
    }
}
