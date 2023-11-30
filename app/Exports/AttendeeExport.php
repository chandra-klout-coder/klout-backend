<?php

namespace App\Exports;

use App\Models\Event;
use App\Models\Company;
use App\Models\Industry;
use App\Models\JobTitle;
use App\Models\Attendee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendeeExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $attendees = Attendee::all();

        $attendeeData = array();

        $i = 0;

        foreach ($attendees as $row) {

            $attendeeData[] = array(
                'id' => $i+1,
                'event' => Event::where('id', $row->event_id)->first()->title,
                'first_name' => $row->first_name,
                'last_name' => $row->last_name,
                'phone_number' => $row->phone_number,
                'email' => $row->email_id,
                'company' => Company::where('id', $row->company)->first()->name,
                'industry' => Industry::where('id', $row->industry)->first()->name,
                'job_title' => JobTitle::where('id', $row->job_title)->first()->name,
            );
            $i++;
        }

        return $attendees;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Event',
            'First Name',
            'Last Name',
            'Phone Number',
            'Email',
            'Company',
            'Industry',
            'Job Title'
        ];

    }
}
