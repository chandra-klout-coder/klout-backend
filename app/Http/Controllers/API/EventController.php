<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Event;
use Ramsey\Uuid\Uuid;
use App\Models\Attendee;
use Illuminate\Http\Request;
use App\Services\SmsServices;
use App\Services\EmailService;
use App\Mail\EventReminderEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\City;
use App\Models\State;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    private $emailService;
    private $smsService;

    public function __construct(EmailService $emailService, SmsServices $smsService)
    {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
    }
    /**
     * Display Dashboard Widgets (Analytics)
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userId = Auth::id();

        $event_data = [];

        $events = Event::where('user_id', $userId)->get()->toArray();

        $total_attendee = $total_accepted = $total_rejected = $total_not_accepted = 0;

        foreach ($events as $event) {

            $total_attendee = Attendee::where('user_id', $userId)->where('event_id', $event['id'])->count();

            $total_accepted = Attendee::where('user_id', $userId)->where('event_id', $event['id'])->where('profile_completed', 1)->count();

            $total_not_accepted = Attendee::where('user_id', $userId)->where('event_id', $event['id'])->where('profile_completed', 0)->count();

            $total_rejected = Attendee::where('user_id', $userId)->where('event_id', $event['id'])->where('profile_completed', 2)->count();

            $event_data1 = array(
                'total_attendee' => $total_attendee,
                'total_accepted' => $total_accepted,
                'total_not_accepted' => $total_not_accepted,
                'total_rejected' => $total_rejected
            );

            $eventDetails = [];

            $city = City::where('id', $event['city'])->first();

            $state = State::where('id', $event['state'])->first();

            $eventDetails = array(
                "id" => $event['id'],
                "uuid" => $event['uuid'],
                "user_id" => $event['user_id'],
                "title" => $event['title'],
                "description" => $event['description'],
                "event_date" => $event['event_date'],
                "location" => !empty($city->name) ? $city->name : "Others",
                "start_time" => $event['start_time'],
                "start_time_type" => $event['start_time_type'],
                "end_time" => $event['end_time'],
                "end_time_type" => $event['end_time_type'],
                "image" => $event['image'],
                "event_venue_name" => $event['event_venue_name'],
                "event_venue_address_1" => $event['event_venue_address_1'],
                "event_venue_address_2" => $event['event_venue_address_2'],
                "city" => !empty($city->name) ? $city->name : "Others",
                "state" => !empty($state->name) ? $state->name : "Others",
                "country" => Country::where('id', $event['country'])->first()->name,
                "pincode" => $event['pincode'],
                "created_at" => $event['created_at'],
                "updated_at" => $event['updated_at'],
                "status" => $event['status'],
                "end_minute_time" => $event['end_minute_time'],
                "start_minute_time" => $event['start_minute_time'],
                "qr_code" => $event['qr_code'],
                "start_time_format" => $event['start_time_format'],
                "feedback" => $event['feedback'],
                "event_start_date" => $event['event_start_date'],
                "event_end_date" =>  $event['event_end_date'],
                "why_attend_info" =>  $event['why_attend_info'],
                "more_information" => $event['more_information'],
                "t_and_conditions" => $event['t_and_conditions']
            );

            $event_data[] = array_merge($eventDetails, $event_data1);
            unset($eventDetails);
        }

        if ($events) {
            return response()->json([
                'status' => 200,
                'message' => 'All Events',
                'data' => $event_data
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'Event not Found',
                'data' => []
            ]);
        }
    }

    //Process Deep Link For Mobile App and Event
    public function processEvent(Request $request)
    {
        // Retrieve the event UUID from the query parameters
        $eventUuid = $request->input('eventuuid');

        if ($eventUuid) {
            $events = Event::where('uuid', $eventUuid)->first();

            if ($events) {

                return response()->json([
                    'status' => 200,
                    'message' => 'Event Details',
                    'data' => $events
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => 'Event Not Found'
                ]);
            }
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Event Not Found'
            ]);
        }
    }

    //City Wise Event
    public function city_wise_event(Request $request)
    {
        $events = Event::where('status', '==', 1)->where('city', $request->city_id)->get()->toArray();

        if ($events) {
            return response()->json([
                'status' => 200,
                'message' => 'City-Wise Events',
                'data' => $events
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'Event not Found',
            ]);
        }
    }

    //Attendee List
    public function all_events_attendee_list(Request $request)
    {
        $email = $request->input('email');

        if (!empty($email)) {

            $events = Event::all()->toArray();

            $event_data = [];

            $total_attendee = $total_accepted = $total_rejected = $total_not_accepted = 0;

            $user_invitation_request = 0;

            foreach ($events as $event) {

                $total_attendee = Attendee::where('event_id', $event['id'])->count();

                $total_accepted = Attendee::where('event_id', $event['id'])->where('profile_completed', 1)->count();

                $total_not_accepted = Attendee::where('event_id', $event['id'])->where('profile_completed', 0)->count();

                $total_rejected = Attendee::where('event_id', $event['id'])->where('profile_completed', 2)->count();

                $event_data1 = array(
                    'total_attendee' => $total_attendee,
                    'total_accepted' => $total_accepted,
                    'total_not_accepted' => $total_not_accepted,
                    'total_rejected' => $total_rejected
                );

                $attend = Attendee::where('event_id', $event['id'])->where('email_id', $email)->first();

                if (!empty($attend)) {

                    $user_invitation_request = $attend->user_invitation_request;
                } else {
                    $user_invitation_request = 0;
                }

                $eventDetails = [];

                $city = City::where('id', $event['city'])->first();

                $state = State::where('id', $event['state'])->first();

                $eventDetails = array(
                    "id" => $event['id'],
                    "uuid" => $event['uuid'],
                    "user_id" => $event['user_id'],
                    "title" => $event['title'],
                    "description" => $event['description'],
                    "event_date" => $event['event_date'],
                    "location" => !empty($city->name) ? $city->name : "Others",
                    "start_time" => $event['start_time'],
                    "start_time_type" => $event['start_time_type'],
                    "end_time" => $event['end_time'],
                    "end_time_type" => $event['end_time_type'],
                    "image" => $event['image'],
                    "event_venue_name" => $event['event_venue_name'],
                    "event_venue_address_1" => $event['event_venue_address_1'],
                    "event_venue_address_2" => $event['event_venue_address_2'],
                    "city" => !empty($city->name) ? $city->name : "Others",
                    "state" => !empty($state->name) ? $state->name : "Others",
                    "country" => Country::where('id', $event['country'])->first()->name,
                    "pincode" => $event['pincode'],
                    "created_at" => $event['created_at'],
                    "updated_at" => $event['updated_at'],
                    "status" => $event['status'],
                    "end_minute_time" => $event['end_minute_time'],
                    "start_minute_time" => $event['start_minute_time'],
                    "qr_code" => $event['qr_code'],
                    "start_time_format" => $event['start_time_format'],
                    "feedback" => $event['feedback'],
                    "event_start_date" => $event['event_start_date'],
                    "event_end_date" =>  $event['event_end_date'],
                    "why_attend_info" =>  $event['why_attend_info'],
                    "more_information" => $event['more_information'],
                    "t_and_conditions" => $event['t_and_conditions'],
                    "user_invitation_request" => $user_invitation_request
                );

                $event_data[] = array_merge($eventDetails, $event_data1);
                unset($eventDetails);
            }


            if ($events) {
                return response()->json([
                    'status' => 200,
                    'message' => 'All Events',
                    'data' => $event_data
                ]);
            } else {
                return response()->json([
                    'status' => 200,
                    'message' => 'Event not Found',
                    'data' => []
                ]);
            }
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'Email Required',
                'data' => []
            ]);
        }
    }


    public function all_events()
    {
        $events = Event::all()->toArray();

        $event_data = [];

        $total_attendee = $total_accepted = $total_rejected = $total_not_accepted = 0;

        foreach ($events as $event) {

            $total_attendee = Attendee::where('event_id', $event['id'])->count();

            $total_accepted = Attendee::where('event_id', $event['id'])->where('profile_completed', 1)->count();

            $total_not_accepted = Attendee::where('event_id', $event['id'])->where('profile_completed', 0)->count();

            $total_rejected = Attendee::where('event_id', $event['id'])->where('profile_completed', 2)->count();

            $event_data1 = array(
                'total_attendee' => $total_attendee,
                'total_accepted' => $total_accepted,
                'total_not_accepted' => $total_not_accepted,
                'total_rejected' => $total_rejected
            );

            $eventDetails = [];

            $city = City::where('id', $event['city'])->first();

            $state = State::where('id', $event['state'])->first();

            $eventDetails = array(
                "id" => $event['id'],
                "uuid" => $event['uuid'],
                "user_id" => $event['user_id'],
                "title" => $event['title'],
                "description" => $event['description'],
                "event_date" => $event['event_date'],
                "location" => !empty($city->name) ? $city->name : "Others",
                "start_time" => $event['start_time'],
                "start_time_type" => $event['start_time_type'],
                "end_time" => $event['end_time'],
                "end_time_type" => $event['end_time_type'],
                "image" => $event['image'],
                "event_venue_name" => $event['event_venue_name'],
                "event_venue_address_1" => $event['event_venue_address_1'],
                "event_venue_address_2" => $event['event_venue_address_2'],
                "city" => !empty($city->name) ? $city->name : "Others",
                "state" => !empty($state->name) ? $state->name : "Others",
                "country" => Country::where('id', $event['country'])->first()->name,
                "pincode" => $event['pincode'],
                "created_at" => $event['created_at'],
                "updated_at" => $event['updated_at'],
                "status" => $event['status'],
                "end_minute_time" => $event['end_minute_time'],
                "start_minute_time" => $event['start_minute_time'],
                "qr_code" => $event['qr_code'],
                "start_time_format" => $event['start_time_format'],
                "feedback" => $event['feedback'],
                "event_start_date" => $event['event_start_date'],
                "event_end_date" =>  $event['event_end_date'],
                "why_attend_info" =>  $event['why_attend_info'],
                "more_information" => $event['more_information'],
                "t_and_conditions" => $event['t_and_conditions']
            );

            $event_data[] = array_merge($eventDetails, $event_data1);
            unset($eventDetails);
        }


        if ($events) {
            return response()->json([
                'status' => 200,
                'message' => 'All Events',
                'data' => $event_data
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'Event not Found',
                'data' => []
            ]);
        }
    }

    //Accept or Decline Event Invitation
    public function accept_decline_event_invitation(Request $request)
    {
        $event_uuid = $request->input('event_uuid');
        $email = $request->input('email');
        $phone_number = $request->input('phone_number');
        $acceptance = $request->input('acceptance');

        if ((!empty($email) || !empty($phone_number)) && !empty($event_uuid)) {

            $invitations = DB::table('attendees')
                ->where('attendees.email_id', $email)
                ->where('event_id', $event_uuid)
                ->orWhere('attendees.phone_number', $phone_number)
                ->where('event_invitation', 1)
                ->first();

            if ($invitations && $acceptance) {

                $accept = Attendee::where('id', $invitations->id)
                    ->update(['event_invitation' => 1]);

                if ($accept) {
                    return response()->json([
                        'status' => 200,
                        'message' => 'Event Invitation Accepted Successfully.',
                    ]);
                }
            } else {

                $accept = Attendee::where('id', $invitations->id)
                    ->update(['event_invitation' => 0]);

                if ($accept) {
                    return response()->json([
                        'status' => 200,
                        'message' => 'Event Invitation Declined.',
                    ]);
                }
            }
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'User not Found.Please Contact to Organizer.',
            ]);
        }
    }

    // Accept Event Invitaions
    public function accept_event_invitation(Request $request)
    {
        $email = $request->input('email');
        $phone_number = $request->input('phone_number');

        if (!empty($email) || !empty($phone_number)) {

            $invitations = DB::table('attendees')
                ->where('attendees.email_id', $email)
                ->orWhere('attendees.phone_number', $phone_number)
                ->where('event_invitation', 1)
                ->first();

            if ($invitations) {

                $acceptacne = Attendee::where('id', $invitations->id)
                    ->update(['event_invitation' => 1]);

                if ($acceptacne) {
                    return response()->json([
                        'status' => 200,
                        'message' => 'Event Invitation Accepted Successfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 200,
                    'message' => 'Attendee not Found.',
                ]);
            }
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'User not Found.Please Contact to Organizer.',
            ]);
        }
    }

    // Request Event Invitaions
    public function request_event_invitation(Request $request)
    {
        $email = $request->input('email_id');
        $phone_number = $request->input('phone_number');
        $event_id = $request->input('event_id');

        if (!empty($email) || !empty($phone_number) || !empty($event_id)) {

            $invitations = DB::table('attendees')
                ->where('email_id', $email)
                ->orWhere('phone_number', $phone_number)
                ->where('event_invitation', 0)
                ->first();

            if ($invitations->user_invitation_request == '0') {

                $requestInvite = Attendee::where('id', $invitations->id)
                    ->update(['user_invitation_request' => 1]);

                if ($requestInvite) {
                    return response()->json([
                        'status' => 200,
                        'message' => 'Event Invitation Requested Successfully',
                    ]);
                }
            } else {

                $validator = Validator::make($request->all(), [
                    'first_name' => 'required|max:30',
                    'last_name' => 'required|max:30',
                    'job_title' => 'required|max:100',
                    'company_name' => 'required|max:50',
                    'industry' => 'required',
                    'email_id' => 'required|email',
                    'phone_number' => 'required'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => 422,
                        'errors' => $validator->errors()
                    ]);
                }

                $userID = Event::where('id', $request->event_id)->first()->user_id;

                $attendee = new Attendee();

                $attendee->uuid = Uuid::uuid4()->toString();
                $attendee->user_id = $userID;
                $attendee->event_id = $event_id;
                $attendee->first_name = strtolower(strip_tags($request->first_name));
                $attendee->last_name = strtolower(strip_tags($request->last_name));
                $attendee->job_title = $request->job_title;
                $attendee->company_name = strip_tags($request->company_name);
                $attendee->industry = strip_tags($request->industry);
                $attendee->email_id = strtolower(strip_tags($request->email_id));
                $attendee->phone_number = empty($request->phone_number) ? '' : $request->phone_number;

                $attendee->alternate_mobile_number = '';
                $attendee->website = '';
                $attendee->linkedin_page_link = '';
                $attendee->company_turn_over = '';
                $attendee->employee_size = '';
                $attendee->status = 0;
                $attendee->profile_completed = true;
                $attendee->event_invitation = 0;
                $attendee->user_invitation_request = 1;

                $success = $attendee->save();

                if ($success) {
                    return response()->json([
                        'status' => 200,
                        'message' => 'Event Invitation Requested Successfully.',
                    ]);
                }
            }
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'User not Found.Please contact to Organizer.',
            ]);
        }
    }

    //On Event Details page - Attendee details 
    public function event_details_attendee_list(Request $request)
    {
        $email = $request->input('email');
        $phone_number = $request->input('phone_number');
        $event_uuid = $request->input('event_uuid');

        if (!empty($email) || !empty($phone_number)) {

            $event = DB::table('events')
                ->where('uuid', $event_uuid)
                ->first();

            if (!empty($event)) {

                $data = [];

                $invitations = DB::table('attendees')
                ->where('event_id', $event->id)
                ->orWhere('phone_number', $phone_number)
                ->orWhere('attendees.email_id', $email)
                    ->first();

                if (!empty($invitations)) {
                    $user_invitation_request = $invitations->user_invitation_request;
                } else {
                    $user_invitation_request = 0;
                }

                $eventDetails = [];

                $city = City::where('id', $event->city)->first();

                $state = State::where('id', $event->state)->first();

                $eventDetails = array(
                    "id" => $event->id,
                    "uuid" => $event->uuid,
                    "user_id" => $event->user_id,
                    "title" => $event->title,
                    "description" => $event->description,
                    "event_date" => $event->event_date,
                    "location" => !empty($city->name) ? $city->name : "Others",
                    "start_time" => $event->start_time,
                    "start_time_type" => $event->start_time_type,
                    "end_time" => $event->end_time,
                    "end_time_type" => $event->end_time_type,
                    "image" => $event->image,
                    "event_venue_name" => $event->event_venue_name,
                    "event_venue_address_1" => $event->event_venue_address_1,
                    "event_venue_address_2" => $event->event_venue_address_2,
                    "city" => !empty($city->name) ? $city->name : "Others",
                    "state" => !empty($state->name) ? $state->name : "Others",
                    "country" => Country::where('id', $event->country)->first()->name,
                    "pincode" => $event->pincode,
                    "created_at" => $event->created_at,
                    "updated_at" => $event->updated_at,
                    "status" => $event->status,
                    "end_minute_time" => $event->end_minute_time,
                    "start_minute_time" => $event->start_minute_time,
                    "qr_code" => $event->qr_code,
                    "start_time_format" => $event->start_time_format,
                    "feedback" => $event->feedback,
                    "event_start_date" => $event->event_start_date,
                    "event_end_date" =>  $event->event_end_date,
                    "why_attend_info" =>  $event->why_attend_info,
                    "more_information" => $event->more_information,
                    "t_and_conditions" => $event->t_and_conditions,
                    "user_invitation_request" => $user_invitation_request,
                    "attendee_details" => $invitations
                );

                $invitationArray = [];

                $invitationArray = json_decode(json_encode($invitations), true);
                $event_data = json_decode(json_encode($eventDetails), true);

                $data = array_merge($event_data, $invitationArray);

                if (count($data) > 0) {
                    return response()->json([
                        'status' => 200,
                        'message' => 'Attendee Details for Event',
                        'data' => $data
                    ]);
                } else {
                    return response()->json([
                        'status' => 200,
                        'message' => 'Events Invitation not Found.Please contact to Organizer.',
                    ]);
                }
            }
        }
    }

    // Event Invitation List for Attendee -- Mobile App
    public function event_invitation_list(Request $request)
    {
        $email = $request->input('email');
        $phone_number = $request->input('phone_number');

        if (!empty($email) || !empty($phone_number)) {

            $invitations = DB::table('attendees')
                ->where('attendees.email_id', $email)
                ->orWhere('attendees.phone_number', $phone_number)
                ->where('event_invitation', 1)
                ->get();

            $data = [];

            foreach ($invitations as $key => $invitation) {

                $event_details = DB::table('events')
                    ->where('id', $invitation->event_id)
                    ->get();

                if ($event_details) {
                    $event_details = json_decode(json_encode($event_details), true);
                } else {
                    $event_details = [];
                }

                $invitationArray = json_decode(json_encode($invitation), true);

                $data[$key] = array_merge($invitationArray, ['event_details' => $event_details]);
            }

            if (count($data) > 0) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Event Invitation List',
                    'data' => $data
                ]);
            } else {
                return response()->json([
                    'status' => 200,
                    'message' => 'Events Invitation not Found.Please contact to Organizer.',
                ]);
            }
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Events Invitation not Found.Please contact to Organizer.',
            ]);
        }
    }
    //Recommended Events for User -- Mobile App
    public function recommended_events()
    {
        $events = Event::where('event_date', '>=', now()->toDateString())
            ->orderBy('created_at', 'desc')->get();

        if (!empty($events)) {
            return response()->json([
                'status' => 200,
                'message' => 'Recommended Events',
                'data' => $events
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Recommended Events not Found.',
            ]);
        }
    }

    //Add 0 in single Digit
    public function prepandZerorIfSingleDigit($number)
    {
        $numberString = (string)$number;

        if (strlen($numberString) === 1) {
            return '0' . $numberString;
        }

        return $numberString;
    }
    /**
     * Store a newly created event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $userId = Auth::id();

        //input validation 
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:100',
            'description' => 'required',
            'event_date' => 'required|date',
            'image' => 'required|image|mimes:jpeg,png,jpg|max:4098',
            'event_venue_name' => 'required|max:255',
            'start_time' => 'required',
            'start_minute_time' => 'required',
            'end_time' => 'required',
            'end_minute_time' => 'required',
            'event_venue_address_1' => 'required',
            'city' => 'required|max:50',
            'state' => 'required|max:50',
            'country' => 'required|max:50',
            'pincode' => 'required|min:6|max:6',
            'event_start_date' => 'nullable|date',
            'event_end_date' => 'nullable|date|after_or_equal:event_start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        }

        $event = new Event();

        $event->uuid = Uuid::uuid4()->toString();
        $event->user_id = isset($userId) ? $userId : $request->user_id;
        $event->title = ucfirst($request->title);
        $event->description = $request->description;
        $event->event_date = $request->event_start_date;

        $event->event_start_date = $request->event_start_date;
        $event->start_time = $this->prepandZerorIfSingleDigit($request->start_time);
        $event->start_minute_time = $this->prepandZerorIfSingleDigit($request->start_minute_time);
        $event->start_time_type = strtoupper($request->start_time_type);

        $event->event_end_date = $request->event_end_date;
        $event->end_time = $this->prepandZerorIfSingleDigit($request->end_time);
        $event->end_minute_time = $this->prepandZerorIfSingleDigit($request->end_minute_time);
        $event->end_time_type = strtoupper($request->end_time_type);

        $event_time = $this->prepandZerorIfSingleDigit($request->start_time) . ':' . $this->prepandZerorIfSingleDigit($request->start_minute_time) . ':00 ' . strtoupper($request->start_time_type);
        $carbonTime = Carbon::createFromFormat('h:i:s A', $event_time);

        $event->start_time_format = $carbonTime->format('H:i:s');

        //Handle image upload and store the image path
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            // $imagePath = $image->store('images', 'public');
            $filename = time() . '.' . $extension;
            $image->move(public_path('uploads/events/'), $filename);
            $event->image = 'uploads/events/' . $filename;
        }

        // $event->event_venue = strip_tags($request->event_venue_name);
        $event->event_venue_name = strip_tags($request->event_venue_name);
        $event->event_venue_address_1 = strip_tags($request->event_venue_address_1);
        $event->event_venue_address_2 = strip_tags($request->event_venue_address_2);
        $event->city = strip_tags($request->city);
        $event->location = strip_tags($request->city);
        $event->state =  strip_tags($request->state);
        $event->country =  strip_tags($request->country);
        $event->pincode = $request->pincode;
        $event->feedback = $request->feedback;
        $event->why_attend_info = $request->why_attend_info;
        $event->more_information = $request->more_information;
        $event->t_and_conditions = $request->t_and_conditions;
        $event->status = $request->status;

        $success = $event->save();

        if ($success) {

            // Generate QR code for the event
            // $eventUrl = route('events.show', ['uuid' => $event->uuid]);
            // $qrCodePath = public_path('uploads/qrcodes/' . $event->uuid . '.png');
            // QrCode::format('png')->size(200)->generate($eventUrl, $qrCodePath);


            $uuidValue = $event->uuid;

            $eventUrl = "https://kloutclub.page.link/?link=https://www.klout.club?eventuuid=" . $uuidValue . "&apn=com.klout.app&isi=6475306206&ibi=com.klout.app";

            $qrCodePath = public_path('uploads/qrcodes/' . $event->uuid . '.png');

            QrCode::format('png')->size(200)->generate($eventUrl, $qrCodePath);

            //Update the event record with the QR code path
            $qr_code = 'uploads/qrcodes/' . $event->uuid . '.png';

            $event->update(['qr_code' => $qr_code]);

            return response()->json([
                'status' => 200,
                'message' => 'Event Created Successfully'
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Something Went Wrong. Please try again later.'
            ]);
        }
    }

    /**
     * Display the specified event.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function display($id)
    {
        $event = Event::where('uuid', $id)->get();

        $eventDetails = [];

        foreach ($event as $row) {

            $city = City::where('id', $row->city)->first();

            $state = State::where('id', $row->state)->first();

            $eventDetails = array(
                "id" => $row->id,
                "uuid" => $row->uuid,
                "user_id" => $row->user_id,
                "title" => $row->title,
                "description" => $row->description,
                "event_date" => $row->event_date,
                "location" => !empty($city->name) ? $city->name : "Others",
                "start_time" => $row->start_time,
                "start_time_type" => $row->start_time_type,
                "end_time" => $row->end_time,
                "end_time_type" => $row->end_time_type,
                "image" => $row->image,
                "event_venue_name" => $row->event_venue_name,
                "event_venue_address_1" => $row->event_venue_address_1,
                "event_venue_address_2" => $row->event_venue_address_2,
                "city" => !empty($city->name) ? $city->name : "Others",
                "state" => !empty($state->name) ? $state->name : "Others",
                "country" => Country::where('id', $row->country)->first()->name,
                "pincode" => $row->pincode,
                "created_at" => $row->created_at,
                "updated_at" => $row->updated_at,
                "status" => $row->status,
                "end_minute_time" => $row->end_minute_time,
                "start_minute_time" => $row->start_minute_time,
                "qr_code" => $row->qr_code,
                "start_time_format" => $row->start_time_interval,
                "feedback" => $row->feedback,
                "event_start_date" => $row->event_start_date,
                "event_end_date" =>  $row->event_end_date,
                "why_attend_info" =>  $row->why_attend_info,
                "more_information" => $row->more_information,
                "t_and_conditions" => $row->t_and_condition
            );
        }

        if ($eventDetails) {

            return response()->json([
                'status' => 200,
                'message' => 'Event Details',
                'data' => $eventDetails
            ]);
        } else {

            return response()->json([
                'status' => 400,
                'message' => 'Event Not Found.'
            ]);
        }
    }

    public function show($id)
    {
        //Get details of event 
        $event = Event::where('uuid', $id)->first();

        if ($event) {

            return response()->json([
                'status' => 200,
                'message' => 'Event Details',
                'data' => $event
            ]);
        } else {

            return response()->json([
                'status' => 400,
                'message' => 'Event Not Found.'
            ]);
        }
    }

    /**
     * Update the specified event.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $userId = Auth::id();

        $validator = Validator::make($request->all(), [
            'title' => 'required|max:100',
            'description' => 'required',
            'event_date' => 'required|date',
            'event_venue_name' => 'required|max:255',
            'start_time' => 'required',
            'start_minute_time' => 'required',
            'end_time' => 'required',
            'end_minute_time' => 'required',
            'event_venue_address_1' => 'required',
            'city' => 'required|max:50',
            'state' => 'required|max:50',
            'country' => 'required|max:50',
            'pincode' => 'required|min:6|max:6',
            'event_start_date' => 'nullable|date',
            'event_end_date' => 'nullable|date|after_or_equal:event_start_date',
        ]);

        if ($request->hasFile('image')) {
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg|max:4098',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ]);
            }
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        } else {

            //Update Event 
            // $event = Event::find($id);
            $event = Event::where('uuid', $id)->first();

            if ($event) {

                $event->user_id = isset($userId) ? $userId : $request->user_id;
                $event->title = ucfirst($request->title);
                $event->description = ucfirst(strip_tags($request->description));
                $event->event_date = $request->event_date;

                $event->event_start_date = $request->event_start_date;
                $event->start_time = $this->prepandZerorIfSingleDigit($request->start_time);
                $event->start_minute_time = $this->prepandZerorIfSingleDigit($request->start_minute_time);
                $event->start_time_type = strtoupper($request->start_time_type);

                $event->event_end_date = $request->event_end_date;
                $event->end_time = $this->prepandZerorIfSingleDigit($request->end_time);
                $event->end_minute_time = $this->prepandZerorIfSingleDigit($request->end_minute_time);
                $event->end_time_type = strtoupper($request->end_time_type);

                $event_time = $this->prepandZerorIfSingleDigit($request->start_time) . ':' . $this->prepandZerorIfSingleDigit($request->start_minute_time) . ':00 ' . strtoupper($request->start_time_type);
                $carbonTime = Carbon::createFromFormat('h:i:s A', $event_time);

                $event->start_time_format = $carbonTime->format('H:i:s');

                //Handle image upload and store the image path
                if ($request->hasFile('image')) {

                    $path = $event->image;

                    if (Storage::exists($path)) {
                        Storage::delete($path);
                    }

                    $image = $request->file('image');
                    $extension = $image->getClientOriginalExtension();
                    $filename = time() . '.' . $extension;
                    $image->move(public_path('uploads/events/'), $filename);
                    $event->image = 'uploads/events/' . $filename;
                }

                // $event->event_venue = strip_tags($request->event_venue_name);
                $event->event_venue_name = strip_tags($request->event_venue_name);
                $event->event_venue_address_1 = strip_tags($request->event_venue_address_1);
                $event->event_venue_address_2 = strip_tags($request->event_venue_address_2);
                $event->city = strip_tags($request->city);
                $event->location = strip_tags($request->city);
                $event->state =  strip_tags($request->state);
                $event->country =  strip_tags($request->country);
                $event->pincode = $request->pincode;
                $event->feedback = $request->feedback;
                $event->why_attend_info = $request->why_attend_info;
                $event->more_information = $request->more_information;
                $event->t_and_conditions = $request->t_and_conditions;

                // if ($request->status === '2') {

                //     $attendeeList = Attendee::where('event_id', $id)->get();

                //     foreach ($attendeeList as $row) {
                //         //send mail and sms
                //         $changed_password_success_message = "Event Cancelled";

                //         $this->emailService->sendEventCancelledEmail($row->email_id, 'Klout: Event Cancelled', $changed_password_success_message);
                //     }
                // }

                $event->status = $request->status;
                $success = $event->update();

                if ($success) {

                    return response()->json([
                        'status' => 200,
                        'message' => 'Event Updated Successfully'
                    ]);
                } else {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Something Went Wrong. Please try again later.'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => 'Event not Found.'
                ]);
            }
        }
    }

    /**
     * Remove the specified event.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        //Delete event
        $event = Event::find($id);

        if ($event) {

            $imagePath = public_path($event->image);
            $qrCodePath = public_path($event->qr_code);

            // Check if the file exists
            if (File::exists($imagePath) || File::exists($qrCodePath)) {
                // Delete the file
                File::delete($imagePath);
                File::delete($qrCodePath);
            }

            $event->attendees()->delete();

            // Delete related notifications
            $event->notifications()->delete();

            // Delete related Sms notifications
            $event->smsnotifications()->delete();

            $deleted = $event->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Event Deleted Successfully.'
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Data not Found.'
            ]);
        }
    }

    /**
     * Send Remainder to all Attendees for Today
     */
    public function sendReminderOnStartDate()
    {
        // Get events that have the start date as today
        $events = Event::where('start_date', now()->toDateString())->get();
        // $events = Event::where('status', '=', 0)->get();

        foreach ($events as $event) {

            // Send reminder email to all attendees for the current event
            $attendees = $event->attendees;

            foreach ($attendees as $record) {

                //Mail Content
                $event_attendee_details = array(
                    'event_details' => $event,
                    'attendee_details' => $record,
                    'title' => $event->title
                );

                $message = 'Hello ' . $record->first_name . '! This is a reminder for the event "' . $event['title'] . '" starting at ' . $event['event_date'];

                // $this->smsService->sendSMS('+91' . $record['phone_number'], $message); //Enable for Email trigger

                Mail::to($record['email_id'])->send(new EventReminderEmail($event_attendee_details));
            }
        }

        return response()->json(['message' => 'Reminder emails sent successfully']);
    }

    /**
     * Send Remainder to all Attendee for an event before an hour
     */
    public function sendReminderOneHourBeforeStartTime()
    {

        //Get the current Time in Indian timezone
        $indianTimeNow = Carbon::now();

        //Calculate one hour from now in Indian TimeZone
        $oneHourFromNow = $indianTimeNow->copy()->addHour();

        //Format the time in "H:i:s" Format
        $timeToCheck = $oneHourFromNow->format('H:i:s');

        // Get events that have the start time one hour from now in Indian timezone
        $oneHourFromNow = now()->addHour();

        // $custom_time = "13:22:00";
        // $events = Event::where('start_time_format', $custom_time)->get();
        // $events = Event::where('status', '=', 0)->get();

        $events = Event::where('start_time_format', $oneHourFromNow->format('H:i:s'))->get();

        foreach ($events as $event) {
            // Send reminder email to all attendees for the current event
            $attendees = $event->attendees; // Assuming you have a relationship set up for attendees in the Event model

            foreach ($attendees as $record) {

                //Mail Content
                $event_attendee_details = array(
                    'event_details' => $event,
                    'attendee_details' => $record,
                    'title' => $event->title
                );

                $message = 'Hello ' . $record->first_name . '! This is a reminder for the event "' . $event['title'] . '" starting at ' . $event['event_date'];

                $this->smsService->sendSMS('+91' . $record['phone_number'], $message); //Enable for Trigger SMS

                Mail::to($record['email_id'])->send(new EventReminderEmail($event_attendee_details));
            }
        }

        return response()->json(['message' => 'Reminder emails sent successfully']);
    }
}
