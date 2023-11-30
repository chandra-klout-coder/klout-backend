<?php

namespace App\Http\Controllers\API;

// use Image;
use Dompdf\Dompdf;
use App\Models\Event;
use Twilio\Rest\Client;
use App\Models\Attendee;
use App\Mail\MyTestEmail;
use Illuminate\Http\Request;
use App\Services\SmsServices;
use App\Services\EmailService;
use Spatie\Browsershot\Browsershot;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

use Ramsey\Uuid\Uuid;

use App\Helpers\HashidsHelper;

class AttendeeController extends Controller
{
    private $emailService;
    private $smsService;

    public function __construct(EmailService $emailService, SmsServices $smsService)
    {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
    }

    public function sendsms()
    {

        if ($this->smsService->sendSMS('+918709289369', 'You are invited to the event!')) {
            return response()->json(['message' => 'Attendee created successfully'], 201);
        }
    }

    public function sendmail()
    {
        $data = ['message' => 'This is the email message.'];

        Mail::to('chandra.bhushan@digimantra.com')->send(new MyTestEmail($data));

        return response()->json(['message' => 'Email sent successfully']);
    }

    //Upload Attendee
    public function upload(Request $request, $event_id)
    {
        $userId = Auth::id();

        $validator = Validator::make($request->all(), [
            'file' => 'required|max:9048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'error' => $validator->errors()->first()
            ]);
        }

        $file = $request->file('file');

        $path = $file->store('temp'); // Store the file temporarily

        $rows = Excel::toArray([], $path)[0]; // Get all rows from the Excel sheet

        $column_data = $rows[0];

        if (isset($rows) && !empty($rows) && is_array($rows) && (count($rows) > 1)) {

            if (
                $rows[0][0] === "first_name"
                && $rows[0][1] === "last_name"
                && $rows[0][2] === "job_title"
                && $rows[0][3] === "company_name"
                && $rows[0][4] === "industry"
                && $rows[0][5] === "email"
                && $rows[0][6] === "phone_number"
                && $rows[0][7] === "alternate_mobile_number"
                && $rows[0][8] === "website"
                && $rows[0][9] === "status"
                && $rows[0][10] === "employee_size"
                && $rows[0][11] === "company_turn_over"
                && $rows[0][12] === "linkedin_page_link"
            ) {

                $not_valid_data = [];

                $firstNameError = $lastNameError = $jobTitleError = $companyNameError =
                    $industryNameError = $emailError =  $statusError = $duplicateEmails = 0;

                $insert_records = $uninsert_records = 0;

                unset($rows[0]);

                foreach ($rows as $index => $row) {

                    $error = false;

                    // array:12 [
                    //     0 => "chandra"
                    //     1 => "sharma"
                    //     2 => "ATL"
                    //     3 => "Digimantra"
                    //     4 => "IT"
                    //     5 => "c@gmail.com"
                    //     6 => 8709289369
                    //     7 => 3434343433
                    //     8 => "www.com.com"
                    //     9 => "Delegates"
                    //     10 => 200
                    //     11 => 2000000
                    //     12 => "https://linked.com/com"
                    //   ]


                    if (empty($rows[$index][0]) || (strlen($rows[$index][0]) > 30) || $rows[$index][0] === null) {
                        $firstNameError += 1;
                        $error = true;
                    } elseif (empty($rows[$index][1]) || (strlen($rows[$index][1]) > 30) || $rows[$index][1] === null) {
                        $lastNameError += 1;
                        $error = true;
                    } elseif (empty($rows[$index][2]) || (strlen($rows[$index][2]) > 100) || $rows[$index][2] === null) {
                        $jobTitleError += 1;
                        $error = true;
                    } elseif (empty($rows[$index][3]) || (strlen($rows[$index][3]) > 50) || $rows[$index][3] === null) {
                        $companyNameError += 1;
                        $error = true;
                    } elseif (empty($rows[$index][4]) || (strlen($rows[$index][4]) > 30) || $rows[$index][4] === null) {
                        $industryNameError += 1;
                        $error = true;
                    } elseif (!filter_var($rows[$index][5], FILTER_VALIDATE_EMAIL) || (strlen($rows[$index][0]) > 30) || $rows[$index][5] === null) {
                        $emailError += 1;
                        $error = true;
                    } elseif (Attendee::where('email_id', $rows[$index][5])->where('event_id', $event_id)->exists()) {
                        $duplicateEmails += 1;
                        $error = true;
                    }

                    // elseif (!empty($rows[$index][9]) || $rows[$index][9] === null) {

                    //     //$rows[$index][9]
                    //     //empty($rows[$index][9]) || strlen($rows[$index][9] < 30) || 

                    //     $status = strtolower($rows[$index][9]);

                    //     if (
                    //         $status !== "speaker"
                    //         || $status !== "delegate"
                    //         || $status !== "sponsor" || $status !== "panelist" || $status !== "moderator"
                    //     ) {
                    //         $statusError += 1;
                    //         $error = true;
                    //     }
                    // }

                    if (!$error) {

                        $insert_records++;

                        $eventID = Event::where('uuid', $event_id)->first()->id;

                        //Insert Data
                        $attendee = new Attendee([
                            'uuid' => Uuid::uuid4()->toString(),
                            'user_id' => $userId,
                            'event_id' => $eventID,
                            'first_name' => $rows[$index][0],
                            'last_name' => $rows[$index][1],
                            'job_title' => $rows[$index][2],
                            'company_name' => $rows[$index][3],
                            'industry' => $rows[$index][4],
                            'email_id' => strtolower($rows[$index][5]),
                            'phone_number' => ($rows[$index][6] === null && empty($rows[$index][6])) ? '' : $rows[$index][6],
                            'alternate_mobile_number' => ($rows[$index][7] !== null && empty($rows[$index][7])) ? '' : $rows[$index][7],
                            'website' => ($rows[$index][8] === null && empty($rows[$index][8])) ? '' : $rows[$index][8],
                            'status' => ($rows[$index][9] === null && empty($rows[$index][9])) ? 'delegate' : strtolower($rows[$index][9]),
                            'employee_size' => ($rows[$index][10] !== null && empty($rows[$index][10])) ? '' : $rows[$index][10],
                            'company_turn_over' => ($rows[$index][11] !== null && empty($rows[$index][11])) ? '' : $rows[$index][11],
                            'linkedin_page_link' => ($rows[$index][12] !== null && empty($rows[$index][12])) ? '' : $rows[$index][12],
                            'profile_completed' => 0
                        ]);

                        $attendee->save();

                        // Remove the temporary file
                        // unlink(storage_path('app/' . $path));
                    }

                    if ($error) {

                        $uninsert_records++;

                        $not_valid_data[] = [
                            'first_name' =>  $rows[$index][0] !== null ? $rows[$index][0] : ' ',
                            'last_name' =>  $rows[$index][1] !== null ? $rows[$index][1] : ' ',
                            'job_title' =>  $rows[$index][2] !== null ? $rows[$index][2] : ' ',
                            'company_name' => $rows[$index][3] !== null ? $rows[$index][3] : ' ',
                            'industry' =>  $rows[$index][4] !== null ? $rows[$index][4] : ' ',
                            'email' =>  $rows[$index][5] !== null ? $rows[$index][5] : ' ',
                            'phone_number' =>  $rows[$index][6] !== null ? $rows[$index][6] : ' ',
                            'alternate_mobile_number' =>  $rows[$index][7] !== null ? $rows[$index][7] : ' ',
                            'website' =>  $rows[$index][8] !== null ? $rows[$index][8] : ' ',
                            'status' =>  $rows[$index][9] !== null ? $rows[$index][9] : ' ',
                            'employee_size' =>  $rows[$index][10] !== null ? $rows[$index][10] : ' ',
                            'company_turn_over' => $rows[$index][11] !== null ? $rows[$index][11] : ' ',
                            'linkedin_page_link' => $rows[$index][12] !== null ? $rows[$index][12] : ' ',
                        ];
                    }
                }

                $errors = array();

                if ($firstNameError  > 0) {
                    $errors['firstNameError'] = 'First Name is missing in ' . $firstNameError . ' rows.';
                }
                if ($lastNameError  > 0) {
                    $errors['lastNameError'] =  'Last Name is missing in ' . $lastNameError . ' rows.';
                }
                if ($jobTitleError  > 0) {
                    $errors['jobTitleError'] =  'Job-Title is missing in ' . $jobTitleError . ' rows.';
                }
                if ($companyNameError  > 0) {
                    $errors['companyNameError'] = 'Company Name is missing in ' . $companyNameError . ' rows.';
                }
                if ($industryNameError  > 0) {
                    $errors['industryNameError'] = 'Industry is missing in ' . $industryNameError . ' rows.';
                }
                if ($emailError  > 0) {
                    $errors['emailError'] = 'Email is missing/Invalid/duplicates in ' . $emailError . ' rows.';
                }
                if ($duplicateEmails  > 0) {
                    $errors['duplicateEmails'] = 'Duplicate Emails Found in ' . $duplicateEmails . ' rows.';
                }
                if ($statusError > 0) {
                    $errors['statusError'] = 'Status Error Found in ' . $statusError . ' rows.';
                }

                if (!empty($not_valid_data)) {
                    return response()->json([
                        'status' => 400,
                        'errors' => $errors,
                        'column_data' => $column_data,
                        'invalid_data' => $not_valid_data,
                        'message' => 'Invalid Data Found.Total UnSaved Records - ' . $uninsert_records . ' and Total Save Data - ' . $insert_records . ' Added.'
                    ]);
                } else {
                    return response()->json([
                        'status' => 200,
                        'message' => 'All Attendee details successfully saved. Total UnSaved Records - ' . $uninsert_records . ' and Total Save Data - ' . $insert_records . ' Added.'
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 401,
                    'message' => 'Invalid Excel Format.'
                ]);
            }
        } else {

            return response()->json([
                'status' => 401,
                'message' => 'Please add Data in Excel and try again.'
            ]);
        }
    }

    //Get all attendee
    /**
     * Display a listing of the Attendees.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userId = Auth::id();

        $attendees = Attendee::where('user_id', $userId)->get();

        if ($attendees) {
            return response()->json([
                'status' => 200,
                'message' => 'All Attendee List',
                'data' => $attendees
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'Event not Found',
                'data' => []
            ]);
        }
    }

    //Get Attendee List by Event ID
    public function getAttendeeByEventID($eventId)
    {
        $userId = Auth::id();

        $attendees = Attendee::where('user_id', $userId)
            ->where('event_id', $eventId)
            ->get();

        if ($attendees) {
            return response()->json([
                'status' => 200,
                'message' => 'All Attendee List',
                'data' => $attendees
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'Event not Found',
                'data' => []
            ]);
        }
    }

    //Send Mail to attendee 
    public function sendMailToAttendee(Request $request, $attendee_id)
    {
        //Input validation 
        $validator = Validator::make($request->all(), [
            'subject' => 'required|max:200',
            'message' => 'required|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        }

        $subject = htmlspecialchars($request->subject);
        $message = htmlspecialchars($request->message);

        $attendee = Attendee::find($attendee_id);

        // Send the email
        $mail = $this->emailService->sendEmail($attendee->email_id, $subject, $message);

        return response()->json(['status' => 200, 'message' => 'Email sent successfully']);
    }

    //send Sms to attendee 
    public function sendSmsToAttendee(Request $request, $attendee_id)
    {
        //Input validation 
        $validator = Validator::make($request->all(), [
            'message' => 'required|max:200'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        }

        $message = $request->message;

        $attendee = Attendee::find($attendee_id);

        $this->smsService->sendSMS('+91' . $attendee->phone_number, 'Congratulations ' .
            $attendee->first_name . ', You are Awesome.' . $message);

        return response()->json(['status' => 200, 'message' => 'SMS sent successfully']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // get details of event 
        // $attendee = Attendee::find($id);
        $attendee = Attendee::where('uuid', $id)->first();

        $eventID = Event::where('id', $attendee->event_id)->first()->uuid;

        if (!empty($attendee)) {

            return response()->json([
                'status' => 200,
                'message' => 'Attendee Detail',
                'data' => $attendee,
                'event_id' => $eventID
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Attendee Detail Not Found',
            ]);
        }
    }

    //Save Attendee Details  and also use for profile Completion
    public function store(Request $request)
    {
        //save event details
        $userId = Auth::id();

        //input validation 
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:30',
            'last_name' => 'required|max:30',
            'job_title' => 'required|max:100',
            'company_name' => 'required|max:50',
            'industry' => 'required',
            'email_id' => 'required|email',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        }

        if (Attendee::where('email_id', $request->email_id)->where('event_id', $request->event_id)->exists()) {
            return response()->json([
                'status' => 422,
                'errors' => array('email_id' => 'Email has been already taken.')
            ]);
        }

        if (!empty($request->hasFile('image'))) {

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

        $eventID = Event::where('uuid', $request->event_id)->first()->id;
        $attendee = new Attendee();

        $attendee->uuid = Uuid::uuid4()->toString();
        $attendee->user_id = $userId;
        $attendee->event_id = $eventID;
        $attendee->first_name = strtolower(strip_tags($request->first_name));
        $attendee->last_name = strtolower(strip_tags($request->last_name));
        $attendee->job_title = $request->job_title;
        $attendee->company_name = strip_tags($request->company_name);
        $attendee->industry = strip_tags($request->industry);
        $attendee->email_id = strtolower(strip_tags($request->email_id));
        $attendee->phone_number = empty($request->phone_number) ? '' : $request->phone_number;
        $attendee->alternate_mobile_number = empty($request->alternate_mobile_number) ? '' : $request->alternate_mobile_number;
        $attendee->website = empty($request->website) ? '' : $request->website;
        $attendee->linkedin_page_link =  empty($request->linkedin_page_link) ? '' : $request->linkedin_page_link;
        $attendee->company_turn_over = empty($request->company_turn_over) ? '' : $request->company_turn_over;
        $attendee->employee_size = empty($request->employee_size) ? '' : $request->employee_size;
        $attendee->status = strtolower($request->status);
        $attendee->profile_completed = true;

        //Handle image upload and store the image path
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            // $imagePath = $image->store('images', 'public');
            $filename = time() . '.' . $extension;
            $image->move(public_path('uploads/attendee/'), $filename);
            $attendee->image = 'uploads/attendee/' . $filename;
        }

        $success = $attendee->save();

        if ($success) {

            // Generate QR code for the event (you need to have an event ID for this)
            $eventQrCode = QrCode::size(200)
                ->margin(5)
                ->generate(config('app.url') . '/events/' . $attendee->event_id);

            $event_details = Event::find($attendee->event_id);

            $data = array();

            //Send email and SMS to each user
            // $this->smsService->sendSMS('+91' . $attendee['phone_number'], 'Congratulations ' .
            //     $attendee['first_name'] . ' !, You have successfully registered for the ' . ucfirst($event_details->title) . ' on ' . $event_details->event_date . ' at ' . ucfirst($event_details->event_venue_name) . '.  We look forward to seeing you there and hope you have a fantastic time!
            //             If you have any questions or need further assistance, feel free to reach out to us. Thank you, Klout Club');
            // $data = array(
            //     'event_details' => $event_details,
            //     'attendee_details' => $attendee
            // );
            // $this->emailService->sendEmail(
            //     $attendee['email_id'],
            //     'Our Event - Event Details Inside!',
            //     $data
            // );

            return response()->json([
                'status' => 200,
                'message' => 'Attendee Added Successfully',
            ]);
        } else {
            return response()->json([
                'status' => 401,
                'message' => 'Something Went Wrong. Please try again later.'
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //user details
        $userId = Auth::id();

        //input validation 
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:30',
            'last_name' => 'required|max:30',
            'job_title' => 'required|max:100',
            'company_name' => 'required|max:50',
            'industry' => 'required',
            // 'email_id' => 'required|email|unique:attendees',
            // 'phone_number' => 'required|digits:10|unique:attendees',
            // 'website' => 'required',
            'status' => 'required',
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
                'erross' => $validator->errors()
            ]);
        }

        // $attendee = Attendee::find($id);

        $attendee = Attendee::where('uuid', $id)->first();
        $eventID = Event::where('uuid', $request->event_id)->first()->id;

        if ($attendee) {

            $attendee->user_id = $userId;
            $attendee->event_id = $eventID;
            $attendee->first_name = strtolower(strip_tags($request->first_name));
            $attendee->last_name = strtolower(strip_tags($request->last_name));
            $attendee->job_title = $request->job_title;
            $attendee->company_name = strip_tags($request->company_name);
            $attendee->industry = strip_tags($request->industry);
            $attendee->email_id = strtolower(strip_tags($request->email_id));
            $attendee->phone_number = empty($request->phone_number) ? '' : $request->phone_number;
            $attendee->alternate_mobile_number = empty($request->alternate_mobile_number) ? '' : $request->alternate_mobile_number;
            $attendee->website = empty($request->website) ? ' ' : $request->website;
            $attendee->linkedin_page_link =  empty($request->linkedin_page_link) ? '' : $request->linkedin_page_link;
            $attendee->company_turn_over = empty($request->company_turn_over) ? '' : $request->company_turn_over;
            $attendee->employee_size = empty($request->employee_size) ? '' : $request->employee_size;
            $attendee->status = strtolower($request->status);
            $attendee->profile_completed = true;

            //Handle image upload and store the image path
            if ($request->hasFile('image')) {

                $path = $attendee->image;

                if (Storage::exists($path)) {
                    // File exists, proceed with deletion
                    Storage::delete($path);
                }

                $image = $request->file('image');
                $extension = $image->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                $image->move(public_path('uploads/events/'), $filename);
                $attendee->image = 'uploads/events/' . $filename;
            }

            $success = $attendee->update();

            if ($success) {

                return response()->json([
                    'status' => 200,
                    'message' => 'Attendee Updated Successfully'
                ]);
            } else {

                return response()->json([
                    'status' => 401,
                    'message' => 'Something Went Wrong. Please try again later.'
                ]);
            }
        } else {

            return response()->json([
                'status' => 404,
                'message' => 'Data not Found.'
            ]);
        }
    }


    //Virtual Business Card
    public function getVitualBusinessCard($attendee_id)
    {
        $card_details = array();

        $attendee = Attendee::find($attendee_id);

        if (!empty($attendee)) {
            //QR code for an event 
            $event_details = Event::find($attendee->event_id);

            $card_details = array(
                'attendee' => $attendee,
                'event_details' => $event_details
            );

            // Combine attendee details and QR code to create the virtual business card
            // $virtualBusinessCard = view('business_card', [
            //     'attendee' => $attendee,
            //     'qrCode' => $event_details
            // ])->render();

            return response()->json([
                'status' => 200,
                'message' => 'Attendee Virtual Card Details',
                'data' =>  $card_details
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Data Not Found.'
            ]);
        }
    }


    //Delete Attendee
    public function destroy(Request $request, $id)
    {
        //Delete event
        $attendee = Attendee::find($id);

        if ($attendee) {

            $attendee->feedbacks()->delete();

            $deleted = $attendee->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Attendee Deleted Successfully.'
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Data not Found.'
            ]);
        }
    }
}
