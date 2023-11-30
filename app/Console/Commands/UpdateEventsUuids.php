<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Event;
use Ramsey\Uuid\Uuid;

class UpdateEventsUuids extends Command
{
    protected $signature = 'events:update-uuids';

    protected $description = 'Update UUIDs for existing Events';

    public function handle()
    {
        $events = Event::all();

        foreach ($events as $row) {
            $row->update(['uuid' => Uuid::uuid4()->toString()]);
        }

        $this->info('UUIDs updated for events.');
    }
}
