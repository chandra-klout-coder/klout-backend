<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Http\Controllers\API\EventController;

use Illuminate\Support\Facades\Log;


class sendReminderOnStartDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auto:sendReminderOnStartDate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $eventController = new EventController();
        $response =  $eventController->sendReminderOnStartDate();

        if ($response) {
            Log::info('Email Notification for Events Trigger on : ',  now());
        }
        
    }
}
