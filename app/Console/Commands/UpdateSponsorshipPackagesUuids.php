<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\SponsorshipPackages;
use Ramsey\Uuid\Uuid;

class UpdateSponsorshipPackagesUuids extends Command
{
    protected $signature = 'sponsorshipPackages:update-uuids';

    protected $description = 'Update UUIDs for existing Sponsorship Packages';

    public function handle()
    {
        $SponsorshipPackages = SponsorshipPackages::all();

        foreach ($SponsorshipPackages as $row) {
            $row->update(['uuid' => Uuid::uuid4()->toString()]);
        }

        $this->info('UUIDs updated for Sponsorship Packages.');
    }
}
