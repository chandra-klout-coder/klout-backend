<?php

namespace App\Http\Controllers\API;

use App\Mail\AttendeeNotification;
use Illuminate\Support\Facades\Mail;

use Carbon\Carbon;
use App\Models\Event;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

use App\Models\Notification;
use App\Services\SmsServices;
use App\Services\EmailService;
use App\Models\SmsNotification;
use App\Mail\EventReminderEmail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Mail\EventReminderEmailInInterval;
use App\Models\Attendee;

use Illuminate\Support\Facades\Http;

class NotificationController extends Controller
{
    private $emailService;
    private $smsService;

    public function __construct(EmailService $emailService, SmsServices $smsService)
    {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
    }

    //Send SMS Message from Text Local
    //  public function sendSmsMessage(Request $request)
    public function sendSmsMessage($firstName, $eventTitle, $eventDateTime,  $phone_number)
    {

        // $firstName = "Chandra";
        // $phone_number = "8709289369";
        // $eventTitle = "Event 1";
        // $eventDateTime = "23 May 2024";

        $apiKey = urlencode(Config('app.textlocal_api_key'));

        $phone_number = "91" . $phone_number;

        $numbers = array($phone_number);

        $sender = urlencode(Config('app.textlocal_sender'));

        $content = "Hi " . $firstName . ", just a reminder for our event " . $eventTitle . " on " . $eventDateTime . ". We look forward to seeing you there! 

Regards,
KloutClub by Insightner Marketing Services";

        $message = rawurlencode($content);

        $numbers = implode(',', $numbers);

        $data = array('apikey' => $apiKey, 'numbers' => $numbers, "sender" => $sender, "message" => $message);

        $ch = curl_init('https://api.textlocal.in/send/');

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);

        $responseData = json_decode($response, true);

        if ($responseData['status'] === "success") {
            return true;
        } else {
            return false;
        }
    }

    // public function sendWhatsappMessage(Request $request)
    // Send Whatsapp Message - IMI Connect
    public function sendWhatsappMessage($variable1, $variable2, $mobile_number)
    {
        $service_key = config('app.whatsapp_service_key');
        $app_id = config('app.whatsapp_app_id');
        $template_id = config('app.whatsapp_template_id');

        //Testing Data
        $variable1 = "Test User";
        $variable2 = "SRN9492294";
        $mobile_number = "918709289369";

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'key' => $service_key,
        ])->post('https://api.imiconnect.io/resources/v1/messaging', [
            'appid' => $app_id,
            'deliverychannel' => 'WhatsApp',
            'message' => [
                'template' => $template_id,
                'parameters' => [
                    'variable1' => $variable1,
                    'variable2' => $variable2,
                ],
            ],
            'destination' => [
                [
                    'waid' => [
                        $mobile_number,
                    ],
                ],
            ],
        ]);

        $result = $response->json();

        if ($result['response']['0']['transid']) {
            return true;
        } else {
            return false;
        }
    }

    // public function sendInterkartMsg(Request $request)
    public function sendInterkartMsg($phoneNumber, $message)
    {
        $apiKey = 'TWZVdTM1cmxJV2FHdnJlQ2EzOXQyMy1EN003YS1fR1BFaVFrdWtmMnQwZzo=';
        //$phoneNumber = '+919810699887';

        $data = [
            "fullPhoneNumber" => $phoneNumber,
            "type" => "Text",
            "data" => [
                "message" => $message
            ]
        ];

        // '{
        //     "fullPhoneNumber": "+919810699887",
        //     "type": "Text",
        //     "data": {
        //         "message": "This msg is sent via API"
        //     }
        // }', 

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.interakt.ai/v1/public/message/',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . $apiKey,
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        if ($response) {
            return true;
        }
    }

    public function timezone()
    {
        $currentTime = now();
        echo "Current System Time" . $currentTime;

        $serverTimezone = date_default_timezone_get();

        echo "AWS EC2 System Time" . $serverTimezone;
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

        $notifications = Notification::where('start_date', '<=', now())->where('delivery_schedule', 'later')->get();

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
        // "next_notify_date_time" => ""
        // "next_notify_date_type" => ""

        if (isset($notifications) && !empty($notifications)) {

            foreach ($notifications as $notification) {

                $event = $notification->event;

                $attendees = $event->attendees;

                $subject  = $notification->subject;

                $message = $notification->message;

                $sender = json_decode($notification->send_to);

                $eventStartDateTime = $reminderTime = $eventEndDateTime = "";

                if (empty($notification->next_notify_date_time)) {

                    //(Start Date Time :'Y-m-d H:i:s')  -- 24 hour Format
                    $eventStartDateTime = $this->getTimeInStandardFormat($notification->start_date, $notification->start_date_time, $notification->start_date_type);
                    // "2024-05-20 01:00:00" AM

                    //2024-05-20 ---> 01:00 AM , 02:00 AM, 03:00 AM, 04:00 AM    till   2024-05-20
                    $reminderTime = Carbon::parse($eventStartDateTime)->addHours($notification->hour_interval);

                    //(End Date Time : 'Y-m-d H:i:s') -- 24 hour Format
                    $eventEndDateTime = $this->getTimeInStandardFormat($notification->end_date, $notification->end_date_time, $notification->end_date_type);
                } else {

                    //(Start Date Time :'Y-m-d H:i:s')  -- 24 hour Format
                    $eventStartDateTime = $this->getTimeInStandardFormat($notification->start_date, $notification->next_notify_date_time, $notification->next_notify_date_type);
                    // "2024-05-20 01:00:00" AM

                    //2024-05-20 ---> 01:00 AM , 02:00 AM, 03:00 AM, 04:00 AM    till   2024-05-20
                    $reminderTime = Carbon::parse($eventStartDateTime)->addHours($notification->hour_interval);

                    //(End Date Time : 'Y-m-d H:i:s') -- 24 hour Format
                    $eventEndDateTime = $this->getTimeInStandardFormat($notification->end_date, $notification->end_date_time, $notification->end_date_type);
                }

                if ($reminderTime <= $eventEndDateTime) {

                    //Live
                    if ($currentDateTime === $reminderTime) {

                        // Testing
                        // if ($currentDateTime) {

                        foreach ($attendees as $attendee) {

                            foreach ($sender as $role) {

                                if (
                                    $role === "All" ||
                                    ($role === "Speaker" && $attendee->status === "speaker") ||
                                    ($role === "Delegate" && $attendee->status === "delegate") ||
                                    ($role === "Sponsor" && $attendee->status === "sponsor") ||
                                    ($role === "Moderator" && $attendee->status === "moderator") ||
                                    ($role === "Panelist" && $attendee->status === "panelist")
                                ) {

                                    if ($notification->send_method === "email") {

                                        Mail::to($attendee->email_id)->send(new AttendeeNotification($attendee, $subject, $message));
                                    } else if ($notification->send_method === "whatsapp") {

                                        $this->sendWhatsappMessage($attendee->first_name, $event->title, $attendee->phone_number);
                                    } else if ($notification->send_method === "sms") {

                                        $eventStartDateTime = $this->getTimeInStandardFormat($event->start_date, $event->start_time, $event->start_time_type);

                                        // Convert time to AM/PM format
                                        $eventDateTime = date("Y-m-d h:i:s A", strtotime($eventStartDateTime));

                                        $this->sendSmsMessage($attendee->first_name, $event->title, $eventDateTime, $attendee->phone_number);
                                    }
                                }
                            }
                        }

                        //update next Notification time
                        $carbonInstance = Carbon::parse($reminderTime);

                        // Extract the date and time
                        $date = $carbonInstance->toDateString(); // "2024-05-20"
                        $time = $carbonInstance->toTimeString(); // "01:00:00"
                        $nextHoursTime = $carbonInstance->format('h'); // "01"
                        $nextTimeFormat = $carbonInstance->format('A'); // "01:00"

                        if (!empty($carbonInstance) && ($reminderTime < $eventEndDateTime)) {

                            $notificationUpate = Notification::findOrFail($notification->id);

                            $notificationUpate->next_notify_date_time = $nextHoursTime;
                            $notificationUpate->next_notify_date_type = $nextTimeFormat;
                            $notificationUpate->save();
                        }

                        return response()->json(['status' => 200, 'message' => 'Reminder sent successfully']);
                    } else {
                        return response()->json(['status' => 200, 'message' => 'Notification In Queue (P).']);
                    }
                } else {
                    return response()->json(['status' => 400, 'message' => 'Event Expired.']);
                }
            }
        } else {
            return response()->json(['status' => 400, 'message' => 'Notifications not Found.']);
        }
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

        //Input validation 
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

            $event_uuid = $request->input('event_id');

            $send_method = $request->input('send_method');

            $send_to =  $request->input('send_to');

            $sendToRoles = explode(",", $send_to);

            $subject = $request->input('subject');
            $message =  $request->input('message');

            $delivery_schedule = $request->input('delivery_schedule');

            //Delivery Notifications Now
            if ($delivery_schedule === "now") {

                if (!empty($subject) && !empty($message) && $send_method === "email") {

                    $event = Event::where('uuid', $event_uuid)->first();
                    $eventId = $event->id;

                    //Send First Time Email Notifications
                    $attendees = Attendee::where('event_id', $eventId)->get();

                    $uniqueAttendees = $attendees->unique('email_id');

                    //"All", "Speaker", "Delegate", "Sponsor", "Moderator"
                    foreach ($uniqueAttendees as $row) {

                        foreach ($sendToRoles as $role) {

                            if ($send_to === "All") {

                                Mail::to($row->email_id)->send(new AttendeeNotification($row, $subject, $message));
                            } else if (($role === "Speaker") && $row->status === "speaker") {

                                Mail::to($row->email_id)->send(new AttendeeNotification($row, $subject, $message));
                            } else if (($role === "Delegate") && $row->status === "delegate") {

                                Mail::to($row->email_id)->send(new AttendeeNotification($row, $subject, $message));
                            } else if (($role === "Sponsor") && $row->status === "sponsor") {

                                Mail::to($row->email_id)->send(new AttendeeNotification($row, $subject, $message));
                            } else if (($role === "Moderator") && $row->status === "moderator") {

                                Mail::to($row->email_id)->send(new AttendeeNotification($row, $subject, $message));
                            } else if (($role === "Panelist") && $row->status === "panelist") {

                                Mail::to($row->email_id)->send(new AttendeeNotification($row, $subject, $message));
                            }
                        }
                    }
                } else if ($send_method === "whatsapp") {

                    $event = Event::where('uuid', $event_uuid)->first();
                    $eventId = $event->id;

                    $attendees = Attendee::where('event_id', $eventId)->get();
                    $uniqueAttendees = $attendees->unique('email_id');

                    //"All", "Speaker", "Delegate", "Sponsor", "Moderator", "panellist"
                    foreach ($uniqueAttendees as $row) {

                        foreach ($sendToRoles as $role) {

                            if ($send_to === "All") {
                                $this->sendInterkartMsg($row->phone_number, $message);
                                //$this->sendWhatsappMessage($row->first_name, $event->title, $row->phone_number);
                            } else if (($role === "Speaker") && $row->status === "speaker") {
                                $this->sendInterkartMsg($row->phone_number, $message);
                                //$this->sendWhatsappMessage($row->first_name, $event->title, $row->phone_number);
                            } else if (($role === "Delegate") && $row->status === "delegate") {
                                $this->sendInterkartMsg($row->phone_number, $message);
                                //$this->sendWhatsappMessage($row->first_name, $event->title, $row->phone_number);
                            } else if (($role === "Sponsor") && $row->status === "sponsor") {
                                $this->sendInterkartMsg($row->phone_number, $message);
                                // $this->sendWhatsappMessage($row->first_name, $event->title, $row->phone_number);
                            } else if (($role === "Moderator") && $row->status === "moderator") {
                                $this->sendInterkartMsg($row->phone_number, $message);
                                //$this->sendWhatsappMessage($row->first_name, $event->title, $row->phone_number);
                            } else if (($role === "Panelist") && $row->status === "panelist") {
                                $this->sendInterkartMsg($row->phone_number, $message);
                                //$this->sendWhatsappMessage($row->first_name, $event->title, $row->phone_number);
                            }
                        }
                    }
                } else if ($send_method === "sms") {


                    $event = Event::where('uuid', $event_uuid)->first();
                    $eventId = $event->id;

                    $eventStartDateTime = $this->getTimeInStandardFormat($event->start_date, $event->start_time, $event->start_time_type);

                    // Convert time to AM/PM format
                    $eventDateTime = date("Y-m-d h:i:s A", strtotime($eventStartDateTime));

                    $attendees = Attendee::where('event_id', $eventId)->get();
                    $uniqueAttendees = $attendees->unique('email_id');

                    //"All", "Speaker", "Delegate", "Sponsor", "Moderator", "panellist"
                    foreach ($uniqueAttendees as $row) {

                        foreach ($sendToRoles as $role) {

                            if ($send_to === "All") {
                                $this->sendSmsMessage($row->first_name, ucfirst($event->title), $eventDateTime,  $row->phone_number);
                            } else if (($role === "Speaker") && $row->status === "speaker") {
                                $this->sendSmsMessage($row->first_name, ucfirst($event->title), $eventDateTime,  $row->phone_number);
                            } else if (($role === "Delegate") && $row->status === "delegate") {
                                $this->sendSmsMessage($row->first_name, ucfirst($event->title), $eventDateTime,  $row->phone_number);
                            } else if (($role === "Sponsor") && $row->status === "sponsor") {
                                $this->sendSmsMessage($row->first_name, ucfirst($event->title), $eventDateTime,  $row->phone_number);
                            } else if (($role === "Moderator") && $row->status === "moderator") {
                                $this->sendSmsMessage($row->first_name, ucfirst($event->title), $eventDateTime,  $row->phone_number);
                            } else if (($role === "Panelist") && $row->status === "panelist") {
                                $this->sendSmsMessage($row->first_name, ucfirst($event->title), $eventDateTime,  $row->phone_number);
                            }
                        }
                    }
                }
            }

            //Send Email Notifications in some Interval
            $notify = new Notification();

            $roles = $request->send_to;

            $rolesArray = explode(",", $roles);

            $insert_roles = json_encode($rolesArray);

            $eventId = $request->event_id;

            $event = Event::where('uuid', $eventId)->first();

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
                            $notify->event_id = $event->id;
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
                            $notify->delivery_schedule = $request->delivery_schedule;
                            $notify->status = 1;

                            $success = $notify->save();

                            if ($success) {
                                if ($send_method === "email") {
                                    return response()->json([
                                        'status' => 200,
                                        'message' => 'Email Notification Save Successfully.',
                                    ]);
                                }

                                if ($send_method === "sms") {
                                    return response()->json([
                                        'status' => 200,
                                        'message' => 'SMS Notification Save Successfully.',
                                    ]);
                                }

                                if ($send_method === "whatsapp") {
                                    return response()->json([
                                        'status' => 200,
                                        'message' => 'WhatsApp Notification Save Successfully.',
                                    ]);
                                }
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
