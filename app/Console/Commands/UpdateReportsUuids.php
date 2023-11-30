<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;


use App\Models\Report;
use Ramsey\Uuid\Uuid;

class UpdateReportsUuids extends Command
{
    protected $signature = 'reports:update-uuids';

    protected $description = 'Update UUIDs for existing Reports';

    public function handle()
    {
        $reports = Report::all();

        foreach ($reports as $row) {
            $row->update(['uuid' => Uuid::uuid4()->toString()]);
        }

        $this->info('UUIDs updated for Reports.');
    }
    

}
