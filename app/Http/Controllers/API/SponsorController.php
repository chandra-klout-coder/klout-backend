<?php

namespace App\Http\Controllers\API;

use PDF;

use App\Models\Event;
use Twilio\Rest\Client;
use App\Models\Sponsor;
use App\Models\Industry;
use App\Models\Company;
use App\Models\JobTitle;
use App\Models\Attendee;
use App\Models\SponsorshipPackages;
use App\Mail\MyTestEmail;
use Illuminate\Http\Request;
use App\Services\SmsServices;
use App\Services\EmailService;
use App\Exports\AttendeeExport;
use Spatie\Browsershot\Browsershot;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

use App\Helpers\HashidsHelper;

use Ramsey\Uuid\Uuid;
class SponsorController extends Controller
{
    /**
     * Get All Sponsors
     * Display a listing of the Sponsors.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userId = Auth::id();

        $sponsors = Sponsor::where('user_id', $userId)->get();

        $sponsorsData = array();

        foreach ($sponsors as $row) {

            $sponsorsData[] = array(
                "id" => $row->id,
                "uuid" => $row->uuid,
                "user_id" => $row->user_id,
                "event_id" => $row->event_id,
                "event_title" => ($row->event_id !== '0' && !empty($row->event_id)) ? Event::where("id", $row->event_id)->first()->title : 'Not Available',
                "first_name" => $row->first_name,
                "last_name" => $row->last_name,
                "job_title" => !empty($row->job_title_name) ? $row->job_title_name : JobTitle::where("id", $row->job_title)->first()->name,
                "company" => !empty($row->company_name) ? $row->company_name : Company::where("id", $row->company)->first()->name,
                "industry" => Industry::where("id", $row->industry)->first()->name,
                // "file" => $row->file,
                "currency" => !empty($row->currency) ? $row->currency : '',
                "image" => $row->image,
                "brand_name" => $row->brand_name,
                "official_email" => $row->official_email,
                "phone_number" => $row->phone_number,
                "country" => $row->country,
                "city" => $row->city,
                "website" => $row->website,
                "employee_size" => $row->employee_size,
                "linkedin_page_link" => $row->linkedin_page_link,
                // "sponsorship_package" => SponsorshipPackages::where('id', $row->sponsorship_package)->first()->name,
                // "amount" => $row->amount,
                "status" => $row->status,
            );
        }

        if ($sponsors) {
            return response()->json([
                'status' => 200,
                'message' => 'All Sponsors List',
                'data' => $sponsorsData
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'Data not Found',
                'data' => []
            ]);
        }
    }

    /**
     * Get All Sponsors By Event-ID
     * Display a listing of the Sponsors by Event-ID.
     *
     * @param int $eventId
     * @return \Illuminate\Http\Response
     */
    public function getSponsorByEventID($eventId)
    {
        $userId = Auth::id();

        $sponsors = Sponsor::where('user_id', $userId)
            ->where('event_id', $eventId)
            ->get();

        $sponsorsData = array();

        foreach ($sponsors as $row) {

            $sponsorsData[] = array(
                "id" => $row->id,
                "user_id" => $row->user_id,
                "event_id" => $row->event_id,
                "event_title" => ($row->event_id !== '0' && !empty($row->event_id)) ? Event::where("id", $row->event_id)->first()->title : 'Not Available',
                "first_name" => $row->first_name,
                "last_name" => $row->last_name,
                "job_title" => !empty($row->job_title_name) ? $row->job_title_name : JobTitle::where("id", $row->job_title)->first()->name,
                "company" => !empty($row->company_name) ? $row->company_name : Company::where("id", $row->company)->first()->name,
                "industry" => Industry::where("id", $row->industry)->first()->name,
                // "file" => $row->file,
                "currency" => !empty($row->currency) ? $row->currency : '',
                "image" => $row->image,
                "brand_name" => $row->brand_name,
                "official_email" => $row->official_email,
                "phone_number" => $row->phone_number,
                "country" => $row->country,
                "city" => $row->city,
                "website" => $row->website,
                "employee_size" => $row->employee_size,
                "linkedin_page_link" => $row->linkedin_page_link,
                // "sponsorship_package" => SponsorshipPackages::where('id', $row->sponsorship_package)->first()->name,
                // "amount" => $row->amount,
                "status" => $row->status,
            );
        }

        if ($sponsors) {
            return response()->json([
                'status' => 200,
                'message' => 'All Sponsors List',
                'data' => $sponsorsData
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'Event not Found',
                'data' => []
            ]);
        }
    }

    /**
     * Show Sponsor Detail
     * Display Sponsor Details.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // $sponsor = Sponsor::find($id);
        $sponsor = Sponsor::where('uuid', $id)->first();

        $sponsorsData = array();

        $sponsorData = array(
            "id" => $sponsor->id,
            "uuid" => $sponsor->uuid,
            "user_id" => $sponsor->user_id,
            "event_id" => $sponsor->event_id,
            "event_title" => ($sponsor->event_id !== '0' && !empty($sponsor->event_id)) ? Event::where("id", $sponsor->event_id)->first()->title : 'Not Available',
            "first_name" => $sponsor->first_name,
            "last_name" => $sponsor->last_name,
            "job_title" => $sponsor->job_title,
            "industry_id" => $sponsor->industry,
            "company" => $sponsor->company,
            "job_title_name" => !empty($sponsor->job_title_name) ?  $sponsor->job_title_name : JobTitle::where("id", $sponsor->job_title)->first()->name,
            "company_name" => !empty($sponsor->company_name) ? $sponsor->company_name : Company::where("id", $sponsor->company)->first()->name,
            // "file" => $sponsor->file,
            "industry" => Industry::where("id", $sponsor->industry)->first()->name,
            "currency" => !empty($sponsor->currency) ? $sponsor->currency : '',
            "image" => $sponsor->image,
            "brand_name" => $sponsor->brand_name,
            "official_email" => $sponsor->official_email,
            "phone_number" => $sponsor->phone_number,
            "country" => $sponsor->country,
            "city" => $sponsor->city,
            "website" => $sponsor->website,
            "employee_size" => $sponsor->employee_size,
            "linkedin_page_link" => $sponsor->linkedin_page_link,
            // "sponsorship_package_id" => $sponsor->sponsorship_package,
            // "sponsorship_package" => SponsorshipPackages::where('id', $sponsor->sponsorship_package)->first()->name,
            // "amount" => $sponsor->amount,
            "status" => $sponsor->status,
        );

        if (!empty($sponsor)) {

            return response()->json([
                'status' => 200,
                'message' => 'Sponsor Detail',
                'data' => $sponsorData
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Data Not Found',
            ]);
        }
    }

    /**
     * Save Sponsor details.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $userId = Auth::id();

        $event_original_id = Event::where('uuid', $request->event_id)->first()->id;

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:30',
            'last_name' => 'required|max:30',
            'job_title' => 'required|max:100',
            'company' => 'required|max:50',
            'industry' => 'required',
            'official_email' => 'required|email',
            'phone_number' => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        }

        if (Sponsor::where('official_email', $request->official_email)->exists()) {
            return response()->json([
                'status' => 422,
                'errors' => array('official_email' => 'Email has been already taken.')
            ]);
        }



        // if (!empty($request->hasFile('file'))) {

        //     $validator = Validator::make($request->all(), [
        //         'file' => 'required|mimes:pdf|max:2048',
        //     ]);

        //     if ($validator->fails()) {
        //         return response()->json([
        //             'status' => 422,
        //             'errors' => $validator->errors()
        //         ]);
        //     }
        // }

        $sponsor = new Sponsor();

        $sponsor->user_id = $userId;
        $sponsor->uuid = Uuid::uuid4()->toString();
        $sponsor->event_id = $event_original_id;
        $sponsor->first_name = strip_tags($request->first_name);
        $sponsor->last_name = strip_tags($request->last_name);
        $sponsor->job_title = $request->job_title;
        $sponsor->job_title_name = !empty($request->job_title_name) ? $request->job_title_name : '';
        $sponsor->company = $request->company;
        $sponsor->company_name = !empty($request->company_name) ? $request->company_name : '';
        $sponsor->website = $request->website;
        $sponsor->industry = $request->industry;
        $sponsor->official_email = strtolower(strip_tags($request->official_email));
        $sponsor->phone_number = empty($request->phone_number) ? '' : $request->phone_number;
        $sponsor->country = empty($request->country) ? '' : $request->country;
        $sponsor->city = empty($request->city) ? '' : $request->city;
        $sponsor->employee_size = empty($request->employee_size) ? '' : $request->employee_size;
        $sponsor->linkedin_page_link =  empty($request->linkedin_page_link) ? '' : $request->linkedin_page_link;

        // $sponsor->sponsorship_package = empty($request->sponsorship_package) ? '' : $request->sponsorship_package;
        // $sponsor->amount = empty($request->amount) ? '' : $request->amount;
        // $sponsor->currency = empty($request->currency) ? '' : $request->currency;

        $sponsor->brand_name = empty($request->brand_name) ? '' : $request->brand_name;
        $sponsor->status = strtolower($request->status);

        //Sponsor Contract Pdf
        // if ($request->hasFile('file')) {
        //     $file = $request->file('file');
        //     $extension = $file->getClientOriginalExtension();
        //     // $filePath = $file->store('images', 'public');
        //     $filename = time() . '.' . $extension;
        //     $file->move(public_path('uploads/contracts/'), $filename);
        //     $sponsor->file = 'uploads/contracts/' . $filename;
        // }

        //Sponsor Profile Picture
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extension = $image->getClientOriginalExtension();
            // $filePath = $file->store('images', 'public');
            $filename = time() . '.' . $extension;
            $image->move(public_path('uploads/sponsors/'), $filename);
            $sponsor->image = 'uploads/sponsors/' . $filename;
        }

        $success = $sponsor->save();

        if ($success) {
            return response()->json([
                'status' => 200,
                'message' => 'Sponsor Added Successfully',
            ]);
        } else {
            return response()->json([
                'status' => 401,
                'message' => 'Something Went Wrong. Please try again later.'
            ]);
        }
    }

    /**
     * Update Sponsor Details
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, $id)
    {


        $userId = Auth::id();

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:30',
            'last_name' => 'required|max:30',
            'job_title' => 'required|max:100',
            'company' => 'required|max:50',
            'industry' => 'required',
            'official_email' => 'required|email',
            'phone_number' => 'required',
            'status' => 'required',
        ]);

        if (!empty($request->hasFile('file'))) {

            $validator = Validator::make($request->all(), [
                'file' => 'required|mimes:pdf|max:4096',
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

        // $sponsor = Sponsor::find($id);
        $sponsor = Sponsor::where('uuid', $id)->first();

        if ($sponsor) {

            $sponsor->user_id = $userId;
            $sponsor->event_id = $request->event_id;
            $sponsor->first_name = strip_tags($request->first_name);
            $sponsor->last_name = strip_tags($request->last_name);
            $sponsor->website = $request->website;
            $sponsor->job_title = $request->job_title;
            $sponsor->job_title_name = !empty($request->job_title_name) ? $request->job_title_name : '';
            $sponsor->company = $request->company;
            $sponsor->company_name = !empty($request->company_name) ? $request->company_name : '';
            $sponsor->industry = $request->industry;
            $sponsor->official_email = $request->official_email;
            $sponsor->phone_number = empty($request->phone_number) ? '' : $request->phone_number;
            $sponsor->country = empty($request->country) ? '' : $request->country;
            $sponsor->city = empty($request->city) ? '' : $request->city;
            $sponsor->employee_size = empty($request->employee_size) ? '' : $request->employee_size;
            $sponsor->linkedin_page_link =  empty($request->linkedin_page_link) ? '' : $request->linkedin_page_link;

            // $sponsor->sponsorship_package = empty($request->sponsorship_package) ? '' : $request->sponsorship_package;
            // $sponsor->amount = empty($request->amount) ? '' : $request->amount;
            // $sponsor->currency = empty($request->currency) ? '' : $request->currency;

            $sponsor->brand_name = empty($request->brand_name) ? '' : $request->brand_name;
            $sponsor->status = strtolower($request->status);

            //Sponsor Contract Pdf
            // if ($request->hasFile('file')) {

            //     $path = $sponsor->file;

            //     if (Storage::exists($path)) {
            //         // File exists, proceed with deletion
            //         Storage::delete($path);
            //     }

            //     $file = $request->file('file');
            //     $extension = $file->getClientOriginalExtension();
            //     $filename = time() . '.' . $extension;

            //     $file->move(public_path('uploads/contracts/'), $filename);
            //     $sponsor->file = 'uploads/contracts/' . $filename;
            // }

            //Sponsor Profile Picture
            if ($request->hasFile('image')) {

                $path = $sponsor->image;

                if (Storage::exists($path)) {
                    // File exists, proceed with deletion
                    Storage::delete($path);
                }

                $image = $request->file('image');
                $extension = $image->getClientOriginalExtension();
                $filename = time() . '.' . $extension;

                $image->move(public_path('uploads/sponsors/'), $filename);
                $sponsor->image = 'uploads/sponsors/' . $filename;
            }

            $success = $sponsor->update();

            if ($success) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Sponsor Updated Successfully.'
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

    /**
     * Send Attendee List PDF By Email 
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $event_id, $file_type
     * @return \Illuminate\Http\Response
     */
    public function sendAttendeeListByEmail(Request $request)
    {
        $file_type = $request->file_type;
        $eventID = $request->event_id;

        //Get Sponsors Details for particular Events
        $sponsors = Attendee::where('event_id', $eventID)->where('status', 'sponsor')->get();

        if (isset($sponsors) && !empty($sponsors)) {

            $attendees = Attendee::where('event_id', $eventID)->get();

            if ($attendees) {
                $attendeeData = array();

                $i = 0;

                foreach ($attendees as $row) {
                    $attendeeData[] = array(
                        'id' => $i + 1,
                        'event' => Event::where('id', $row->event_id)->first()->title,
                        'first_name' => $row->first_name,
                        'last_name' => $row->last_name,
                        'phone_number' => $row->phone_number,
                        'email' => $row->email_id,
                        'company' => $row->company_name,
                        'industry' => $row->industry,
                        'job_title' => $row->job_title,
                    );
                    $i++;
                }

                foreach ($sponsors as $record) {

                    $sponsor_email = "";
                    
                    if ($file_type === "pdf") {
                        
                        //Generate PDF File
                        $pdf = PDF::loadView('pdf.attendee_list', compact('attendeeData'));
                        
                        $pdfFilePath = storage_path('app/temp/attendee_list.pdf');
                        
                        $pdf->save($pdfFilePath);
                        
                        $sponsor_email = $record->email_id;
                
                        Mail::send('emails.attendee_list', [], function ($message) use ($pdfFilePath, $sponsor_email) {
                            $message->to($sponsor_email)->subject('Attendee List');
                            $message->attach($pdfFilePath, [
                                'as' => 'attendee_list.pdf',
                                'mime' => 'application/pdf',
                            ]);
                        });

                        unlink($pdfFilePath); // Clean up the temporary PDF file

                    } elseif ($file_type === "csv") {

                        //Generate CSV File
                        $filename = storage_path('app/attendee_list.csv');
                        $file = fopen($filename, 'w');

                        foreach ($attendeeData as $row) {
                            fputcsv($file, $row);
                        }

                        fclose($file);

                        $filename = storage_path('app/attendee_list.csv');

                        Mail::send('emails.attendee_list', [], function ($message) use ($filename, $sponsor_email) {
                            $message->to($sponsor_email)->subject('Attendee List');
                            $message->attach($filename, [
                                'as' => 'attendee_list.csv',
                                'mime' => 'application/csv',
                            ]);
                        });

                    } else {
                        
                        return response()->json(['status' => 400, 'message' => 'Something Went Wrong.Please try again later.']);
                    }
                }

                return response()->json(['status' => 200, 'message' => 'Attendees List shared with Sponsors successfully.']);
            
            } else {

                return response()->json(['status' => 200, 'message' => 'Sponsors not Found']);
            }
        }
    }


    /**
     * Delete Sponsor 
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        //Delete event
        $sponsor = Sponsor::find($id);

        if ($sponsor) {

            // $sponsor->feedbacks()->delete();

            $deleted = $sponsor->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Sponsor Deleted Successfully.'
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Data not Found.'
            ]);
        }
    }
}
