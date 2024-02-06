<?php

namespace App\Http\Controllers\API;

use DateTime;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Event;
use App\Models\Report;
use App\Models\Attendee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\State;
use App\Models\Country;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;

use Ramsey\Uuid\Uuid;

class ReportController extends Controller
{
    //Total attendee for a particaular Events For Organizer - Dashboard
    public function total_attendees_for_organizer()
    {
        //  $userId = Auth::id();
        $user = auth()->user();
        $userEvents = $user->events()->with('attendees')->get();

        $allAttendees = [];

        foreach ($userEvents as $event) {
            $attendeeList = DB::select("SELECT events.title, attendees.* FROM events 
            LEFT JOIN attendees 
            ON events.id = attendees.event_id 
            WHERE attendees.event_id = " . $event->id);

            $allAttendees = array_merge($allAttendees,   $attendeeList);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Total Attendees (Till Now)',
            'total_attendees' => $allAttendees
        ]);
    }

    //Total attendee for a particaular Events  - Event ID
    public function total_attendees($event_id)
    {
        $event_original_id = Event::where('uuid', $event_id)->first()->id;

        $event = Event::withCount('attendees')->find($event_original_id);

        $totalAttendees = $event->attendees_count;

        $attendee_data = Attendee::where('event_id', $event_original_id)->get();

        $excel_data = [];

        foreach ($attendee_data as $row) {

            $event = Event::where('id', $row->event_id)->first();

            $excel_data[] = array(
                "event_name" => $event->title,
                "first_name" => empty($row->first_name) ? ' ' : $row->first_name,
                "last_name" => empty($row->last_name) ? ' ' : $row->last_name,
                "image" => empty($row->image) ? ' ' : $row->image,
                "job_title" => empty($row->job_title) ? ' ' : $row->job_title,
                "company_name" => empty($row->company_name) ? ' ' : $row->company_name,
                "industry" => empty($row->industry) ? ' ' : $row->industry,
                "email_id" => empty($row->email_id) ? ' ' : $row->email_id,
                "phone_number" => empty($row->phone_number) ? ' ' : $row->phone_number,
                "website" => empty($row->website) ? ' ' : $row->website,
                "linkedin_page_link" => empty($row->linkedin_page_link) ? ' ' : $row->linkedin_page_link,
                "employee_size" => empty($row->employee_size) ? ' ' : $row->employee_size,
                "company_turn_over" => empty($row->company_turn_over) ? ' ' : $row->company_turn_over,
                "status" => empty($row->status) ? ' ' : $row->status,
                "profile_completed" => $row->profile_completed == 1 ? 'Yes' : 'No',
                "alternate_mobile_number" => empty($row->alternate_mobile_number) ? ' ' : $row->alternate_mobile_number
            );
        }

        return response()->json([
            'status' => 200,
            'message' => 'Total No. of Attendees',
            'total_attendees' => $totalAttendees,
            'data' => $attendee_data,
            'excel_data' => $excel_data
        ]);
    }

    //Total attendee for a particaular Events For Organizer
    public function total_number_of_events()
    {
        $userId = Auth::id();

        $user = auth()->user();
        $totalEvents = $user->events()->count();

        $data = Event::where('user_id', $userId)->get();

        return response()->json([
            'status' => 200,
            'message' => 'Total No. of Events',
            'total_events' => $totalEvents,
            'data' => $data
        ]);
    }

    //Upcoming Events for user 
    public function upcoming_events()
    {
        $userId = Auth::id();

        $today = Carbon::today();

        $upcomingEvents = Event::whereDate('event_date', '>=', $today)->where('user_id', $userId)->get()->toArray();

        $total_attendee = $total_accepted = $total_rejected = $total_not_accepted = 0;

        foreach ($upcomingEvents as $event) {

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


        return response()->json([
            'status' => 200,
            'message' => 'Upcoming Events',
            'upcoming_events' => count($upcomingEvents),
            'data' => $event_data
        ]);
    }

    //Total Sponsors for organizer
    public function total_sponsors()
    {
        $user = auth()->user();

        $userEvents = $user->events()->with('attendees')->get();

        $allSponsors = [];

        foreach ($userEvents as $event) {
            $attendeeList = DB::select("SELECT events.title, attendees.* FROM events 
            LEFT JOIN attendees 
            ON events.id = attendees.event_id 
            WHERE attendees.status = 'sponsor' AND attendees.event_id = " . $event->id);

            $allSponsors = array_merge($allSponsors,   $attendeeList);
        }

        return response()->json([
            'status' => 200,
            'message' => 'Total No. of Sponsors ( Till Now )',
            'totalsponsors' => count($allSponsors),
            'data' => $allSponsors
        ]);
    }

    //Total Sponsors for an event
    public function total_sponsors_event($event_id)
    {
        $userId = Auth::id();

        $user = User::find($userId);
        $event = $user->events()->find($event_id);

        $totalSponsors = $event->attendees()->where('status', 'sponsor')->count();

        return response()->json([
            'status' => 200,
            'message' => 'Total No. of Sponsors For Event',
            'totalsponsors' =>  $totalSponsors
        ]);
    }

    //Total Attendee by Type for an Event
    public function total_attendee_type_event($event_id)
    {
        $userId = Auth::id();

        $user = User::find($userId);

        $event = $user->events()->find($event_id);

        $allAttendees = $event->attendees()->get();

        $sponsor = $delegate = $speaker = $panellist = $moderator = 0;

        foreach ($allAttendees as $record) {

            if ($record->status == 'sponsor') {
                $sponsor += 1;
            } elseif ($record->status == 'delegate') {
                $delegate += 1;
            } elseif ($record->status == 'speaker') {
                $speaker += 1;
            } elseif ($record->status == 'panelist') {
                $panellist += 1;
            } elseif ($record->status == 'moderator') {
                $moderator += 1;
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'No. Of Attendee by Type',
            'totalAttendee' => count($allAttendees),
            'sponsor' => $sponsor,
            'delegate' => $delegate,
            'speaker' => $speaker,
            'panelist' => $panellist,
            'moderator' => $moderator
        ]);
    }

    //Profile Completed by Attendee for an Event
    public function attendee_profile_completed($event_id)
    {
        $userId = Auth::id();

        $user = User::find($userId);

        $event = $user->events()->find($event_id);

        $allAttendees = $event->attendees()->get();

        $profile_completed = $profile_not_completed = 0;

        foreach ($allAttendees as $record) {

            if ($record->profile_completed == 1 || $record->profile_completed == true) {
                $profile_completed += 1;
            } elseif ($record->profile_completed == 0 || $record->profile_completed == false) {
                $profile_not_completed += 1;
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'No. Of Attendee by Type',
            'totalAttendee' => count($allAttendees),
            'profile_completed' => $profile_completed,
            'profile_not_completed' => $profile_not_completed
        ]);
    }

    //Reports List
    public function reports()
    {
        $userId = Auth::id();

        $reports = Report::where('user_id', $userId)->get();

        $data = [];

        foreach ($reports  as $record) {

            $dateTime = new DateTime($record->created_at);

            $formattedDateTime = $dateTime->format('Y-m-d H:i:s');

            $data[] = array(
                "id" => $record->id,
                "user_id" => $record->user_id,
                "event_id" => $record->event_id,
                "report_name" => $record->report_name,
                "event_date" => $record->event_date,
                "event_tags" => $record->user_tags,
                "event_attribute" => $record->event_attribute,
                "status" => $record->status,
                "created_at" => $formattedDateTime
            );
        }

        if ($data) {
            return response()->json([
                'status' => 200,
                'message' => 'All Report Details',
                'data' =>  $data
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Report not Found',
                'data' => []
            ]);
        }
    }

    //Generate report in CSV
    public function generateCSV(Request $request)
    {
        $userId = Auth::id();

        $reportName = $request->input('report_name');
        $eventId = $request->input('event_id');
        $eventDate = $request->input('event_date');
        $eventTags = $request->input('event_tags');
        $eventAttribute = $request->input('event_attribute');

        $eventsQuery = Event::where('user_id', $userId)->where('id', $eventId)->first();

        $eventTitle = Event::where('id', $eventId)->first()->title;

        $attendeeList = [];

        if ($eventsQuery->event_start_date === $eventDate) {

            if ($eventAttribute === 'user_data_uploaded_for_the_event') {

                $attendee = Attendee::where('event_id', $eventId)->get();

                foreach ($attendee as $row) {

                    $attendeeList[] = array(
                        "event_name" => $eventTitle,
                        "first_name" => empty($row->first_name) ? ' ' : $row->first_name,
                        "last_name" => empty($row->last_name) ? ' ' : $row->last_name,
                        "image" => empty($row->image) ? ' ' : $row->last_name,
                        "virtual_business_card" => empty($row->virtual_business_card) ? ' ' : $row->virtual_business_card,
                        "job_title" => empty($row->job_title) ? ' ' : $row->job_title,
                        "company_name" => empty($row->company_name) ? ' ' : $row->company_name,
                        "industry" => empty($row->industry) ? ' ' : $row->industry,
                        "email_id" => empty($row->email_id) ? ' ' : $row->email_id,
                        "phone_number" => empty($row->phone_number) ? ' ' : $row->phone_number,
                        "website" => empty($row->website) ? ' ' : $row->website,
                        "linkedin_page_link" => empty($row->linkedin_page_link) ? ' ' : $row->linkedin_page_link,
                        "employee_size" => empty($row->employee_size) ? ' ' : $row->employee_size,
                        "company_turn_over" => empty($row->company_turn_over) ? ' ' : $row->company_turn_over,
                        "status" => empty($row->status) ? ' ' : $row->status,
                        "profile_completed" => $row->profile_completed == 1 ? 'Yes' : 'No',
                        "alternate_mobile_number" => empty($row->alternate_mobile_number) ? ' ' : $row->alternate_mobile_number
                    );
                }

                $report = new Report([
                    'uuid' => Uuid::uuid4()->toString(),
                    'user_id' => $userId,
                    'event_id' => $eventId,
                    'event_date' => $eventDate,
                    'report_name' => $reportName,
                    'event_tags' => $eventTags,
                    'event_attribute' => $eventAttribute,
                    'status' => 1
                ]);

                $report->save();

                return response()->json([
                    'status' => 200,
                    'message' => 'Report Generated Successfully - User Data uploaded for the Event',
                    'data' => $attendeeList
                ]);
            } elseif ($eventAttribute === 'attendance_report') {

                $attendee = Attendee::where('event_id', $eventId)->where('profile_completed', 1)->get();

                foreach ($attendee as $row) {

                    $attendeeList[] = array(
                        "event_name" => $eventTitle,
                        "first_name" => empty($row->first_name) ? ' ' : $row->first_name,
                        "last_name" => empty($row->last_name) ? ' ' : $row->last_name,
                        "image" => empty($row->image) ? ' ' : $row->last_name,
                        "virtual_business_card" => empty($row->virtual_business_card) ? ' ' : $row->virtual_business_card,
                        "job_title" => empty($row->job_title) ? ' ' : $row->job_title,
                        "company_name" => empty($row->company_name) ? ' ' : $row->company_name,
                        "industry" => empty($row->industry) ? ' ' : $row->industry,
                        "email_id" => empty($row->email_id) ? ' ' : $row->email_id,
                        "phone_number" => empty($row->phone_number) ? ' ' : $row->phone_number,
                        "website" => empty($row->website) ? ' ' : $row->website,
                        "linkedin_page_link" => empty($row->linkedin_page_link) ? ' ' : $row->linkedin_page_link,
                        "employee_size" => empty($row->employee_size) ? ' ' : $row->employee_size,
                        "company_turn_over" => empty($row->company_turn_over) ? ' ' : $row->company_turn_over,
                        "status" => empty($row->status) ? ' ' : $row->status,
                        "profile_completed" => $row->profile_completed == 1 ? 'Yes' : 'No',
                        "alternate_mobile_number" => (empty($row->alternate_mobile_number) ||  $row->alternate_mobile_number === null) ? ' ' : $row->alternate_mobile_number
                    );
                }

                $report = new Report([
                    'uuid' => Uuid::uuid4()->toString(),
                    'user_id' => $userId,
                    'event_id' => $eventId,
                    'event_date' => $eventDate,
                    'report_name' => $reportName,
                    'event_tags' => $eventTags,
                    'event_attribute' => $eventAttribute,
                    'status' => 1
                ]);

                $report->save();

                return response()->json([
                    'status' => 200,
                    'message' => 'Report Generated Successfully - Attendance Report',
                    'data' => $attendeeList
                ]);
            }
        } else {

            return response()->json([
                'status' => 400,
                'message' => 'Event Date is Invalid'
            ]);
        }
    }

    //Download Report CSV
    public function downloadCSV($id)
    {
        $userId = Auth::id();

        $reports = Report::where('id', $id)->where('user_id', $userId)->first();

        $reportName = $reports->report_name;
        $eventId = $reports->event_id;
        $eventDate = $reports->event_date;
        $eventTags = $reports->event_tags;
        $eventAttribute = $reports->event_attribute;

        $eventsQuery = Event::where('user_id', $userId)->where('id', $eventId)->first();

        $eventTitle = Event::where('id', $eventId)->first()->title;

        $attendeeList = [];

        if ($eventsQuery->event_start_date === $eventDate) {

            if ($eventAttribute === 'user_data_uploaded_for_the_event') {

                $attendee = Attendee::where('event_id', $eventId)->get();


                foreach ($attendee as $row) {

                    $attendeeList[] = array(
                        "event_name" => $eventTitle,
                        "first_name" => empty($row->first_name) ? ' ' : $row->first_name,
                        "last_name" => empty($row->last_name) ? ' ' : $row->last_name,
                        "image" => empty($row->image) ? ' ' : $row->last_name,
                        "virtual_business_card" => empty($row->virtual_business_card) ? ' ' : $row->virtual_business_card,
                        "job_title" => empty($row->job_title) ? ' ' : $row->job_title,
                        "company_name" => empty($row->company_name) ? ' ' : $row->company_name,
                        "industry" => empty($row->industry) ? ' ' : $row->industry,
                        "email_id" => empty($row->email_id) ? ' ' : $row->email_id,
                        "phone_number" => empty($row->phone_number) ? ' ' : $row->phone_number,
                        "website" => empty($row->website) ? ' ' : $row->website,
                        "linkedin_page_link" => empty($row->linkedin_page_link) ? ' ' : $row->linkedin_page_link,
                        "employee_size" => empty($row->employee_size) ? ' ' : $row->employee_size,
                        "company_turn_over" => empty($row->company_turn_over) ? ' ' : $row->company_turn_over,
                        "status" => empty($row->status) ? ' ' : $row->status,
                        "profile_completed" => $row->profile_completed == 1 ? 'Yes' : 'No',
                        "alternate_mobile_number" => empty($row->alternate_mobile_number) ? ' ' : $row->alternate_mobile_number
                    );
                }

                return response()->json([
                    'status' => 200,
                    'message' => 'Report Generated Successfully - User Data uploaded for the Event',
                    'data' => $attendeeList
                ]);
            } elseif ($eventAttribute === 'attendance_report') {

                $attendee = Attendee::where('event_id', $eventId)->where('profile_completed', 1)->get();

                foreach ($attendee as $row) {

                    $attendeeList[] = array(
                        "event_name" => $eventTitle,
                        "first_name" => empty($row->first_name) ? ' ' : $row->first_name,
                        "last_name" => empty($row->last_name) ? ' ' : $row->last_name,
                        "image" => empty($row->image) ? ' ' : $row->last_name,
                        "virtual_business_card" => empty($row->virtual_business_card) ? ' ' : $row->virtual_business_card,
                        "job_title" => empty($row->job_title) ? ' ' : $row->job_title,
                        "company_name" => empty($row->company_name) ? ' ' : $row->company_name,
                        "industry" => empty($row->industry) ? ' ' : $row->industry,
                        "email_id" => empty($row->email_id) ? ' ' : $row->email_id,
                        "phone_number" => empty($row->phone_number) ? ' ' : $row->phone_number,
                        "website" => empty($row->website) ? ' ' : $row->website,
                        "linkedin_page_link" => empty($row->linkedin_page_link) ? ' ' : $row->linkedin_page_link,
                        "employee_size" => empty($row->employee_size) ? ' ' : $row->employee_size,
                        "company_turn_over" => empty($row->company_turn_over) ? ' ' : $row->company_turn_over,
                        "status" => empty($row->status) ? ' ' : $row->status,
                        "profile_completed" => $row->profile_completed == 1 ? 'Yes' : 'No',
                        "alternate_mobile_number" => (empty($row->alternate_mobile_number) ||  $row->alternate_mobile_number === null) ? ' ' : $row->alternate_mobile_number
                    );
                }

                return response()->json([
                    'status' => 200,
                    'message' => 'Report Generated Successfully - Attendance Report',
                    'data' => $attendeeList
                ]);
            }
        } else {

            return response()->json([
                'status' => 400,
                'message' => 'Event Date is Invalid'
            ]);
        }
    }

    //Remove the specified report
    public function destroy(Request $request, $id)
    {
        //Delete report
        $report = Report::find($id);

        if ($report) {

            $deleted = $report->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Report Deleted Successfully.'
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Data not Found.'
            ]);
        }
    }
}
