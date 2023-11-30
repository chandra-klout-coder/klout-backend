<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EventReminderEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $event_details;

    /**
     * Create a new message instance.
     *
     * @return void
     * 
     */
    public function __construct($event_attendee_details)
    {
        $this->event_details = $event_attendee_details;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.event_reminder')
        ->subject('Event Reminder: ' . $this->event_details['title']);
    }
}
