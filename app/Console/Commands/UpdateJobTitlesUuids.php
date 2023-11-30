<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;


use App\Models\JobTitle;
use Ramsey\Uuid\Uuid;

class UpdateJobTitlesUuids extends Command
{
    protected $signature = 'job-titles:update-uuids';

    protected $description = 'Update UUIDs for existing job titles';

    public function handle()
    {
        $jobTitles = JobTitle::all();

        foreach ($jobTitles as $jobTitle) {
            $jobTitle->update(['uuid' => Uuid::uuid4()->toString()]);
        }

        $this->info('UUIDs updated for job titles.');
    }
 
}
