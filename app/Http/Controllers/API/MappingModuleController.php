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
}
