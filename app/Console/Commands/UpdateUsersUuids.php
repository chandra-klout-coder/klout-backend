<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Ramsey\Uuid\Uuid;

class UpdateUsersUuids extends Command
{
    protected $signature = 'users:update-uuids';

    protected $description = 'Update UUIDs for existing Users';

    public function handle()
    {
        $users = User::all();

        foreach ($users as $row) {
            $row->update(['uuid' => Uuid::uuid4()->toString()]);
        }

        $this->info('UUIDs updated for Users.');
    }  

}
