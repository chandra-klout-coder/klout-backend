<?php

namespace App\Http\Controllers\API;

use App\Models\FeedBack;
use App\Models\User;
use App\Models\Event;
use App\Models\Report;
use App\Models\Attendee;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class FeedBackController extends Controller
{
    /**
     * Display a listing of Feedbacks.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth()->user();

        $userEvents = $user->events()->get();

        $allFeedbacks = [];

        foreach ($userEvents as $event) {

            $attendeeList = DB::select("SELECT events.id as eventId, events.title as eventTitle, attendees.id, attendees.first_name, attendees.last_name, feedbacks.* 
            FROM feedbacks
            LEFT JOIN attendees 
            ON feedbacks.attendee_id = attendees.id 
            INNER JOIN events 
            ON feedbacks.event_id = events.id
            WHERE feedbacks.event_id = " . $event->id);

            $allFeedbacks = array_merge($allFeedbacks, $attendeeList);
        }

        if (!empty($allFeedbacks)) {
            return response()->json([
                'status' => 200,
                'message' => 'All Feedback List',
                'data' => $allFeedbacks
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'Feedback not Found',
                'data' => []
            ]);
        }
    }

    //Test SMS service
    public function message()
    {
        // Account details
        $apiKey = urlencode('MzI1NTc4NGE0OTY1NDQ1Mjc0NzMzNTU4NmE3MjM4NDI=');

        // Message details
        $numbers = '918709289369';

        $sender = urlencode('PABALL');

        $message = rawurlencode('This is your message');

        // Prepare data for POST request
        $data = array('apikey' => $apiKey, 'numbers' => $numbers, "sender" => $sender, "message" => $message);

        // Send the GET request with cURL
        $ch = curl_init('https://api.textlocal.in/send/');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        //Process the response
        if ($response) {
            return response()->json([
                'status' => 200,
                'message' => 'Message send Status',
                'data' => $response
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Something went Wrong.Please try again later.',
                'data' => $response
            ]);
        }
    }

    //Save Attendee Details
    public function store(Request $request)
    {
        //save event details
        $userId = Auth::id();

        //input validation 
        $validator = Validator::make($request->all(), [
            'subject' => 'required',
            'message' => 'required',
            'rating' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'erross' => $validator->errors()
            ], 422);
        }

        $feedback = new FeedBack();

        $feedback->uuid = Uuid::uuid4()->toString();
        $feedback->event_id = $request->event_id;
        $feedback->attendee_id = $request->attendee_id;
        $feedback->attendee_type = $request->attendee_type;
        $feedback->subject = $request->subject;
        $feedback->message = $request->message;
        $feedback->rating = $request->rating;

        $success = $feedback->save();

        if ($success) {

            return response()->json([
                'status' => 200,
                'message' => 'Feedback Added Successfully'
            ]);
        } else {
            return response()->json([
                'status' => 401,
                'message' => 'Something Went Wrong. Please try again later.'
            ]);
        }
    }

    /**
     * Display the specified Feedback.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //get details of Feeback
        $feedback = FeedBack::find($id);

        if (!empty($feedback)) {
            return response()->json([
                'status' => 200,
                'message' => 'Feedback Details',
                'data' => $feedback
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Feedback Not Found'
            ]);
        }
    }

    /**
     * Remove Feedback.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        //Delete Feedback
        $feedback = FeedBack::find($id);

        if ($feedback) {

            $deleted = $feedback->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Feedback Deleted Successfully.'
            ]);
        } else {
            return response()->json([
                'status' => 401,
                'message' => 'Data not Found.'
            ]);
        }
    }
}
