<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Attendee;
use Ramsey\Uuid\Uuid;

class UpdateAttendeesUuids extends Command
{
    protected $signature = 'attendees:update-uuids';

    protected $description = 'Update UUIDs for existing Attendees';

    public function handle()
    {
        $attendees = Attendee::all();

        foreach ($attendees as $row) {
            $row->update(['uuid' => Uuid::uuid4()->toString()]);
        }

        $this->info('UUIDs updated for attendees.');
    }
   
}
