<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Event;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Services\SmsServices;
use App\Services\EmailService;
use App\Models\SmsNotification;
use App\Mail\EventReminderEmail;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Mail\EventReminderEmailInInterval;

class NotificationController extends Controller
{
    private $emailService;
    private $smsService;

    public function __construct(EmailService $emailService, SmsServices $smsService)
    {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
    }

    //Save Attendee Details  and also use for profile Completion
    public function mail_store(Request $request)
    {
        //save event details
        $userId = Auth::id();

        //input validation 
        $validator = Validator::make($request->all(), [
            'event_id' => 'required',
            'send_to' => 'required',
            'send_method' => 'required',
            'subject' => 'required',
            'message' => 'required',
            'start_date' => 'required',
            'start_date_time' => 'required',
            'start_date_type' => 'required',
            'end_date' => 'required',
            'end_date_time' => 'required',
            'end_date_type' => 'required',
            'no_of_times' => 'required',
            'hour_interval' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        } else {

            $notify = new Notification();

            $notify->user_id = $request->user_id;
            $notify->event_id = $request->event_id;
            $notify->send_to = $request->send_to;
            $notify->send_method = $request->send_method;
            $notify->subject = $request->subject;
            $notify->message = $request->message;
            $notify->start_date = $request->start_date;
            $notify->start_date_time = $request->start_date_time;
            $notify->start_date_type = $request->start_date_type;
            $notify->end_date = $request->end_date;
            $notify->end_date_time = $request->end_date_time;
            $notify->end_date_type = $request->end_date_type;
            $notify->no_of_times = $request->no_of_times;
            $notify->hour_interval = $request->hour_interval;
            $notify->status = $request->status;

            $success = $notify->save();

            if ($success) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Reminder for Event Email Scheduled Successfully.',
                ]);
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'Something Went Wrong. Please try again later.'
                ]);
            }
        }
    }

    public function getTimeInStandardFormat($date, $date_time, $date_type)
    {
        // Assuming the input date and time strings
        $inputTime = $date_time . ' ' . $date_type;

        // Combine the date and time strings into a single datetime string
        $datetimeString = $date . ' ' . $inputTime;

        // Parse the datetime string into a Carbon instance
        $carbonDateTime = Carbon::parse($datetimeString);

        // Format the datetime as per your requirement (for example, 'Y-m-d H:i:s')
        $formattedDateTime = $carbonDateTime->format('Y-m-d H:i:s');

        return $formattedDateTime;
    }

    //Send SMS Reminder at regular interval
    public function sendSmsReminderRegularInterval()
    {
        // Get the current date and time
        $currentDateTime = Carbon::now();

        $notifications = SmsNotification::where('start_date', '<=', now())->get();

        foreach ($notifications as $notification) {

            $event = $notification->event;

            $attendees = $event->attendees;

            //(Start Date Time :'Y-m-d H:i:s')  -- 24 hour Format
            $eventStartDateTime = $this->getTimeInStandardFormat($notification->start_date, $notification->start_date_time, $notification->start_date_type);

            $reminderTime = Carbon::parse($eventStartDateTime)->addHours($notification->hour_interval);

            //(End Date Time : 'Y-m-d H:i:s') -- 24 hour Format
            $eventEndDateTime = $this->getTimeInStandardFormat($notification->end_date, $notification->end_date_time, $notification->end_date_type);

            foreach ($attendees as $attendee) {

                //Mail Content
                $event_attendee_details = array(
                    'event_details' => $event,
                    'attendee_details' => $attendee,
                    'title' => $event->title
                );

                while ($reminderTime <= $eventEndDateTime && $currentDateTime >= $reminderTime) {
                    // if ($currentDateTime == $reminderTime) {
                    //SMS Content
                    $message = 'Hello ' . $attendee->first_name . '! This is a reminder for the event at regular Interval"' . $event['title'] . '" starting at ' . $event['event_date'];

                    // $this->smsService->sendSMS('+91' . $attendee['phone_number'], $message);
                    // }
                    $reminderTime->addHours($notification->hour_interval);
                }
            }
        }
        return response()->json(['message' => 'Reminder SMS sent successfully']);
    }

    //Send Email Reminder at regular interval
    public function sendMailReminderRegularInterval()
    {
        // Get the current date and time
        $currentDateTime = Carbon::now();

        $notifications = Notification::where('start_date', '<=', now())->get();

        // $roles = $request->send_to;

        // $rolesArray = explode(",", $roles);

        // $insert_roles = json_encode($rolesArray);

        // dd($notifications[0]);

        // "id" => 9
        // "user_id" => 50
        // "event_id" => 50
        // "send_to" => "["All"]"
        // "send_method" => "email"
        // "subject" => "sfersdg"
        // "message" => "srdgfrsegr"
        // "start_date" => "2023-08-24"
        // "start_date_time" => "01"
        // "start_date_type" => "am"
        // "end_date" => "2023-08-29"
        // "end_date_time" => "01"
        // "end_date_type" => "pm"
        // "no_of_times" => "1"
        // "hour_interval" => "12"
        // "status" => "1"
        // "created_at" => "2023-08-23 15:55:41"
        // "updated_at" => "2023-08-23 15:55:41"

        foreach ($notifications as $notification) {

            $event = $notification->event;

            $attendees = $event->attendees;

            //(Start Date Time :'Y-m-d H:i:s')  -- 24 hour Format
            $eventStartDateTime = $this->getTimeInStandardFormat($notification->start_date, $notification->start_date_time, $notification->start_date_type);

            // "2023-08-24 01:00:00"

            $reminderTime = Carbon::parse($eventStartDateTime)->addHours($notification->hour_interval);


            //(End Date Time : 'Y-m-d H:i:s') -- 24 hour Format
            $eventEndDateTime = $this->getTimeInStandardFormat($notification->end_date, $notification->end_date_time, $notification->end_date_type);


            //get attendee type
            //dd(json_decode($notification->send_to));

            $sender = json_decode($notification->send_to);


            if (in_array('All', $sender)) {

                foreach ($attendees as $attendee) {

                    //Mail Content
                    $event_attendee_details = array(
                        'event_details' => $event,
                        'attendee_details' => $attendee,
                        'title' => $event->title
                    );

                    if ($reminderTime <= $eventEndDateTime) {

                        if ($currentDateTime === $reminderTime) {

                            // SMS Content
                            // $message = 'Hello ' . $attendee->first_name . '! This is a reminder for the event "' . $event['title'] . '" starting at ' . $event['event_date'];
                            // $this->smsService->sendSMS('+91' . $record['phone_number'], $message); //Enable for Email trigger

                            Mail::to($attendee['email_id'])->send(new EventReminderEmailInInterval($event_attendee_details));
                        }
                        $reminderTime->addHours($notification->hour_interval);
                    }
                }
            }
        }
        return response()->json(['message' => 'Reminder Emails sent successfully']);
    }

    //Get Notification List
    public function notifications_list()
    {
        $userId = Auth::id();

        $list = Notification::where('user_id', $userId)->get();

        if (isset($list) && !empty($list)) {
            return response()->json([
                'status' => 200,
                'message' => 'All Notification Schedule List',
                'data' => $list
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'Event not Found',
                'data' => []
            ]);
        }
    }

    //Save Attendee Details and also use for profile Completion
    public function store_notification(Request $request)
    {
        //save event details
        $userId = Auth::id();

        $rolesArray = $insert_roles = [];

        //input validation 
        $validator = Validator::make($request->all(), [
            'event_id' => 'required',
            'send_to' => 'required',
            'send_method' => 'required',
            'message' => 'required',
            'start_date' => 'required',
            'start_date_time' => 'required',
            'start_date_type' => 'required',
            'end_date' => 'required',
            'end_date_time' => 'required',
            'end_date_type' => 'required',
            'no_of_times' => 'required',
            'hour_interval' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        } else {

            $notify = new Notification();

            $roles = $request->send_to;

            $rolesArray = explode(",", $roles);

            $insert_roles = json_encode($rolesArray);

            $eventId = $request->event_id;

            //     "event_id" => "50"
            //     "start_date" => "2023-08-24"
            //     "start_date_time" => "01"
            //     "start_date_type" => "am"
            //     "end_date" => "2023-08-25"
            //     "end_date_time" => "01"
            //     "end_date_type" => "pm"
            //     "no_of_times" => "1"
            //     "hour_interval" => "12"

            $event = Event::where('id', $eventId)->first();

            // "event_start_date" => "2023-08-25"
            // "event_end_date" => "2023-08-30"
            // "start_time" => "09"
            // "start_minute_time" => "30"
            // "start_time_type" => "AM"
            // "end_time" => "06"
            // "end_minute_time" => "30"
            // "end_time_type" => "PM"

            $schedule_start_time = $request->start_date_time . ':00 ' . $request->start_date_type;
            $event_start_time = $event->start_time . ' : ' . $event->start_minute_time . ' ' . $event->start_time_type;

            $schedule_end_time = $request->end_date_time . ':00 ' . $request->end_date_type;
            $event_end_time = $event->start_time . ' : ' . $event->start_minute_time . ' ' . $event->start_time_type;

            // Get the schedule start date
            $scheduleStartDate = Carbon::parse($request->start_date . ' ' . $schedule_start_time); // Output: 2023-08-25 01:00:00

            // Add 12 hours to the start date
            $scheduleIntervalDate = $scheduleStartDate->addHours($request->hour_interval); //2023-08-25 13:00:00 after 12 hours

            // Get the schedule end date
            $scheduleEndDate = Carbon::parse($request->end_date . ' ' . $schedule_end_time); // Output: 2023-08-29 15:00:00

            if ($event->event_start_date >= $request->start_date) {

                if ($event->event_end_date >= $request->end_date) {

                    if ($scheduleStartDate < $scheduleEndDate) {

                        // less than end date time -- after adding interval 
                        if ($scheduleIntervalDate <= $scheduleEndDate) {

                            $notify->user_id = $userId;
                            $notify->event_id = $request->event_id;
                            $notify->send_to = $insert_roles;
                            $notify->send_method = strtolower($request->send_method);
                            $notify->subject = !empty($request->subject) ?  $request->subject : '';
                            $notify->message = $request->message;
                            $notify->start_date = $request->start_date;
                            $notify->start_date_time = $request->start_date_time;
                            $notify->start_date_type = $request->start_date_type;
                            $notify->end_date = $request->end_date;
                            $notify->end_date_time = $request->end_date_time;
                            $notify->end_date_type = $request->end_date_type;
                            $notify->no_of_times = $request->no_of_times;
                            $notify->hour_interval = $request->hour_interval;
                            $notify->status = 1;

                            $success = $notify->save();

                            if ($success) {
                                return response()->json([
                                    'status' => 200,
                                    'message' => 'Reminder for Event SMS Scheduled Successfully.',
                                ]);
                            } else {
                                return response()->json([
                                    'status' => 401,
                                    'message' => 'Something Went Wrong. Please try again later.'
                                ]);
                            }
                        } else {
                            return response()->json([
                                'status' => 400,
                                'message' => 'Please select other Start Date / Interval.'
                            ]);
                        }
                    } else {

                        return response()->json([
                            'status' => 400,
                            'message' => 'Start / End  Date is Invalid'
                        ]);
                    }
                } else {

                    return response()->json([
                        'status' => 400,
                        'message' => 'Start / End  Date is Invalid'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 400,
                    'message' => 'Start / End Date is Invalid'
                ]);
            }
        }
    }
}
