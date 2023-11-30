<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\FeedBack;
use Ramsey\Uuid\Uuid;

class UpdateFeedBacksUuids extends Command
{
    protected $signature = 'feedbacks:update-uuids';

    protected $description = 'Update UUIDs for existing Feedbacks';

    public function handle()
    {
        $feedbacks = FeedBack::all();

        foreach ($feedbacks as $row) {
            $row->update(['uuid' => Uuid::uuid4()->toString()]);
        }

        $this->info('UUIDs updated for Feedbacks.');
    }
}
