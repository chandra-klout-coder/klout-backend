<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

use App\Models\City;
use App\Models\State;
use App\Models\Country;
use App\Models\CompanyData;
use App\Models\IndustryData;
use App\Models\JobTitleData;
use App\Models\UnassignedData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;


class MappingModuleController extends Controller
{
    //Assign Cities Data
    public function assignedCitiesData(Request $request)
    {
        //Raw Data and JSON Array
        $assignData = $request->input('assign_data');
        $city_id = $request->input('city_id');

        if (isset($assignData) && !empty($assignData)) {
            foreach ($assignData as $row) {
                City::create([
                    'name' => $row
                ]);
            }
            return response()->json(
                [
                    'status' => 200,
                    'message' => 'Cities stored successfully.'
                ]
            );
        } else {
            return response()->json(
                [
                    'status' => 400,
                    'message' => 'Incorrect Data.'
                ]
            );
        }
    }

    //Assign State Data.
    public function assignedStatesData(Request $request)
    {
        $assignData = $request->input('assign_data');
        $state_id = $request->input('state_id');

        if (isset($assignData) && !empty($assignData)) {
            foreach ($assignData as $row) {
                State::create([
                    'name' => $row
                ]);
            }
            return response()->json([
                'status' => 200,
                'message' => 'States stored successfully.'
            ]);
        } else {
            return response()->json(
                [
                    'status' => 400,
                    'message' => 'Incorrect Data.'
                ]
            );
        }
    }

    //Assign Country Data.
    public function assignedCountriesData(Request $request)
    {
        $assignData = $request->input('assign_data');
        $country_id = $request->input('country_id');

        if (isset($assignData) && !empty($assignData)) {
            foreach ($assignData as $row) {
                Country::create([
                    'name' => $row
                ]);
            }
            return response()->json([
                'status' => 200,
                'message' => 'Countries stored successfully.'
            ]);
        } else {
            return response()->json(
                [
                    'status' => 400,
                    'message' => 'Incorrect Data.'
                ]
            );
        }
    }

    //Assign Industry Data.
    public function assignedIndustriesData(Request $request)
    {
        $assignData = $request->input('assign_data');
        $industry_id = $request->input('industry_id');


        if (isset($assignData) && !empty($assignData)) {
            foreach ($assignData as $row) {
                IndustryData::create([
                    'name' => $row,
                    'parent_id' => 1
                ]);
            }
            return response()->json([
                'status' => 200,
                'message' => 'Industries stored successfully.'
            ]);
        } else {
            return response()->json(
                [
                    'status' => 400,
                    'message' => 'Incorrect Data.'
                ]
            );
        }
    }


    //Assign Job Title Data.
    public function assignedJobTitlesData(Request $request)
    {
        $assignData = $request->input('assign_data');
        $job_title_id = $request->input('job_title_id');

        if (isset($assignData) && !empty($assignData)) {
            foreach ($assignData as $row) {
                JobTitleData::create([
                    'name' => $row,
                    'parent_id' => 1
                ]);
            }
            return response()->json([
                'status' => 200,
                'message' => 'Job Titles stored successfully.'
            ]);
        } else {
            return response()->json(
                [
                    'status' => 400,
                    'message' => 'Incorrect Data.'
                ]
            );
        }
    }

    public function unassignedData(Request $request)
    {
        $data_module_type = $request->input('data_module_type');

        if (isset($data_module_type) && !empty($data_module_type)) {

            if ($data_module_type === 'industry') {

                $industryData = UnassignedData::where('type', 'industry')->get()->toArray();

                return response()->json([
                    'status' => 200,
                    'message' => 'Unassigned Industry Data',
                    'data' => $industryData
                ]);
            } else if ($data_module_type === 'job_title') {

                $JobTitleData =  UnassignedData::where('type', 'job_title')->get()->toArray();

                return response()->json([
                    'status' => 200,
                    'message' => 'Unassigned Job Title Data',
                    'data' => $JobTitleData
                ]);
            } else if ($data_module_type === "company") {
                $companyData =  UnassignedData::where('type', 'company')->get()->toArray();

                return response()->json([
                    'status' => 200,
                    'message' => 'Unassigned Company Data',
                    'data' => $companyData
                ]);
            } else if ($data_module_type === "country") {
                $countryData =  UnassignedData::where('type', 'country')->get()->toArray();

                return response()->json([
                    'status' => 200,
                    'message' => 'Unassigned Country Data',
                    'data' => $countryData
                ]);
            } else if ($data_module_type === "state") {
                $stateData =  UnassignedData::where('type', 'state')->get()->toArray();

                return response()->json([
                    'status' => 200,
                    'message' => 'Unassigned State Data',
                    'data' => $stateData
                ]);
            } else if ($data_module_type === "city") {
                $cityData =  UnassignedData::where('type', 'city')->get()->toArray();

                return response()->json([
                    'status' => 200,
                    'message' => 'Unassigned City Data',
                    'data' => $cityData
                ]);
            } else {
                return response()->json([
                    'status' => 400,
                    'message' => 'Incorret Parameter.'
                ]);
            }
        }
    }


    //Cities - CRUD
    public function cities()
    {
        $cities = City::all();

        if ($cities) {
            return response()->json([
                'status' => 200,
                'message' => 'All Cities',
                'data' => $cities
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'Data not Found'
            ]);
        }
    }

    //Show City Details
    public function show_cities($id)
    {
        $city = City::where('id', $id)->first();

        if ($city) {

            return response()->json([
                'status' => 200,
                'message' => 'City Details',
                'data' => $city
            ]);
        } else {

            return response()->json([
                'status' => 400,
                'message' => 'Data Not Found.'
            ]);
        }
    }

    // Save Cities
    public function save_cities(Request $request)
    {
        //input validation 
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:100',
            'parent_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        }

        $city = new City();

        // $city->uuid = Uuid::uuid4()->toString();
        $city->name = $request->name;
        $city->parent_id = $request->parent_id;
        $city->state_id = $request->state_id;
        $success = $city->save();

        if ($success) {
            return response()->json([
                'status' => 200,
                'message' => 'City Added Successfully'
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Something Went Wrong. Please try again later.'
            ]);
        }
    }

    // Update Cities
    public function update_cities(Request $request, $id)
    {
        //input validation 
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:100',
            'parent_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        } else {

            $city = City::where('id', $id)->first();

            if ($city) {

                // $city->uuid = Uuid::uuid4()->toString();
                $city->name = $request->name;
                $city->parent_id = $request->parent_id;
                $city->state_id = $request->state_id;
                $success = $city->update();

                if ($success) {

                    return response()->json([
                        'status' => 200,
                        'message' => 'City Updated Successfully'
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
                    'message' => 'Data not Found.'
                ]);
            }
        }
    }

    //Delete City
    public function destroy_cities(Request $request, $id)
    {
        //Delete
        $city = City::find($id);

        if ($city) {
            $deleted = $city->delete();

            return response()->json([
                'status' => 200,
                'message' => 'City Deleted Successfully.'
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Data not Found.'
            ]);
        }
    }


    //States - CRUD
    public function states()
    {
        $states = City::all();

        if ($states) {
            return response()->json([
                'status' => 200,
                'message' => 'All States',
                'data' => $states
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'Data not Found'
            ]);
        }
    }

    //Show State Details
    public function show_states($id)
    {
        $state = State::where('id', $id)->first();

        if ($state) {

            return response()->json([
                'status' => 200,
                'message' => 'State Details',
                'data' => $state
            ]);
        } else {

            return response()->json([
                'status' => 400,
                'message' => 'Data Not Found.'
            ]);
        }
    }

    // Save States
    public function save_states(Request $request)
    {
        //input validation 
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:100',
            'parent_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        }

        $state = new State();

        // $state->uuid = Uuid::uuid4()->toString();
        $state->name = $request->name;
        $state->parent_id = $request->parent_id;
        $state->country_id = $request->country_id;
        $success = $state->save();

        if ($success) {
            return response()->json([
                'status' => 200,
                'message' => 'State Added Successfully'
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Something Went Wrong. Please try again later.'
            ]);
        }
    }

    // Update States
    public function update_states(Request $request, $id)
    {
        //input validation 
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:100',
            'parent_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        } else {

            $state = State::where('id', $id)->first();

            if ($state) {

                // $city->uuid = Uuid::uuid4()->toString();
                $state->name = $request->name;
                $state->parent_id = $request->parent_id;
                $state->country_id = $request->country_id;
                $success = $state->update();

                if ($success) {

                    return response()->json([
                        'status' => 200,
                        'message' => 'State Updated Successfully'
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
                    'message' => 'Data not Found.'
                ]);
            }
        }
    }

    //Delete State
    public function destroy_states(Request $request, $id)
    {
        //Delete
        $state = State::find($id);

        if ($state) {
            $deleted = $state->delete();

            return response()->json([
                'status' => 200,
                'message' => 'State Deleted Successfully.'
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Data not Found.'
            ]);
        }
    }


    //Countries - CRUD
    public function countries()
    {
        $countries = Country::all();

        if ($countries) {
            return response()->json([
                'status' => 200,
                'message' => 'All Countries',
                'data' => $countries
            ]);
        } else {
            return response()->json([
                'status' => 200,
                'message' => 'Data not Found'
            ]);
        }
    }

    //Show countries Details
    public function show_countries($id)
    {
        $countries = Country::where('id', $id)->first();

        if ($countries) {

            return response()->json([
                'status' => 200,
                'message' => 'Country Details',
                'data' => $countries
            ]);
        } else {

            return response()->json([
                'status' => 400,
                'message' => 'Data Not Found.'
            ]);
        }
    }

    // Save Countries
    public function save_countries(Request $request)
    {
        //input validation 
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:100',
            'parent_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        }

        $country = new Country();

        // $country->uuid = Uuid::uuid4()->toString();
        $country->name = $request->name;
        $country->parent_id = $request->parent_id;
        $success = $country->save();

        if ($success) {
            return response()->json([
                'status' => 200,
                'message' => 'Country Added Successfully'
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'Something Went Wrong. Please try again later.'
            ]);
        }
    }

    // Update Cities
    public function update_countries(Request $request, $id)
    {
        //input validation 
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:100',
            'parent_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        } else {

            $country = Country::where('id', $id)->first();

            if ($country) {

                // $city->uuid = Uuid::uuid4()->toString();
                $country->name = $request->name;
                $country->parent_id = $request->parent_id;
                $success = $country->update();

                if ($success) {

                    return response()->json([
                        'status' => 200,
                        'message' => 'Country Updated Successfully'
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
                    'message' => 'Data not Found.'
                ]);
            }
        }
    }

    //Delete City
    public function destroy_countries(Request $request, $id)
    {
        //Delete
        $country = Country::find($id);

        if ($country) {
            $deleted = $country->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Country Deleted Successfully.'
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Data not Found.'
            ]);
        }
    }
}
