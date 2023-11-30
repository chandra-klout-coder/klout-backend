<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Sponsor;
use Ramsey\Uuid\Uuid;

class UpdateSponsorsUuids extends Command
{
    protected $signature = 'sponsors:update-uuids';

    protected $description = 'Update UUIDs for existing Sponsors';

    public function handle()
    {
        $sponsors = Sponsor::all();

        foreach ($sponsors as $row) {
            $row->update(['uuid' => Uuid::uuid4()->toString()]);
        }

        $this->info('UUIDs updated for sponsors.');
    }
}
