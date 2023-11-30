<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\SmsServices;
use App\Services\EmailService;

use Ramsey\Uuid\Uuid;

class UserController extends Controller
{
    private $emailService;
    private $smsService;

    public function __construct(EmailService $emailService, SmsServices $smsService)
    {
        $this->emailService = $emailService;
        $this->smsService = $smsService;
    }

    //Auth - Get User Details
    public function profile()
    {
        $user = Auth::user();

        if ($user) {

            //Return user profile
            return response()->json([
                'status' => 200,
                'message' => 'User-Profile Details',
                'user' => $user
            ]);
        }
    }

    //update profile
    public function updateprofile(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'required | string | max:200',
            'last_name' => 'string | max:200',
            'email' => 'required | email | max: 255',
            'mobile_number' => 'required | min:10 | max:10',
            'company' => 'required',
            'designation' => 'required',
            'address' => 'required',
            'pincode' => 'required',
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

            $errors = $validator->errors();

            return response()->json([
                'status' => 422,
                'message' => 'Please fill all mandatory fields.',
                'error' => $errors,
            ]);
        } else {

            // Update the authenticated user's profile
            $user = Auth::user();
            $user->first_name = $request->input('first_name');
            $user->last_name = $request->input('last_name');
            $user->mobile_number = $request->input('mobile_number');
            $user->company = $request->input('company');
            $user->company_name = !empty($request->input('company_name')) ? $request->input('company_name') : "";
            $user->designation = $request->input('designation');
            $user->designation_name = !empty($request->input('designation_name')) ? $request->input('designation_name') : "";
            $user->address = $request->input('address');

            if ($request->hasFile('image')) {

                $path = $user->image;

                if (Storage::exists($path)) {
                    Storage::delete($path);
                }

                $image = $request->file('image');
                $extension = $image->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                $image->move(public_path('uploads/users/'), $filename);
                $user->image = 'uploads/users/' . $filename;
            }

            $user->save();
        }

        return response()->json([
            'status' => 200,
            'message' => 'Profile Updated Successfully'
        ]);
    }

    //change password
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation Error',
                'error' => $validator->errors()
            ]);
        }

        $user = Auth::user();

        if (Hash::check($request->input('password'), $user->password)) {
            return response()->json(
                [
                    'status' => 403,
                    'message' => 'New password should not be same as old password.'
                ]
            );
        }

        if (trim($request->input('password')) !== trim($request->input('confirm_password'))) {
            return response()->json([
                'status' => 402,
                'message' => 'New password or Confirm Password are not matching.'
            ]);
        }

        if (!Hash::check($request->input('old_password'), $user->password)) {
            return response()->json(
                [
                    'status' => 401,
                    'message' => 'Old password is incorrect.'
                ]
            );
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();

        //send mail and sms
        $changed_password_success_message = "Congratulations ! Password Changed Successfully.";

        $this->emailService->sendChangedPasswordEmail($user->email, 'Klout: Password Changed', $changed_password_success_message);

        return response()->json([
            'status' => 200,
            'message' => 'Password Changed Successfully.Please Login again.'
        ]);
    }
}
