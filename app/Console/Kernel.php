<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //Verify Schduler by Log - schedule:work
        $schedule->call(function () {
            Log::info('Cron job executed at: ' . now());
        })->everyMinute();

        //remainder of Event to attendee 1 hour before 
        $schedule->command('auto:sendReminderOneHourBeforeStartTime')->everyMinute();

        //remainder of Event on the same Date
        $schedule->command('auto:sendReminderOnStartDate')->dailyAt('15:15');

        //remainder of Event to attendee at custom Time Interval by Email
        $schedule->command('auto:sendReminderEmailInterval')->everyMinute();

        //remainder of Event to attendee at custom Time Interval by SMS
        $schedule->command('auto:sendReminderSmsInterval')->everyMinute();

        // $schedule->call('App\Http\Controllers\EventController@sendReminderOnStartDate')
        //     ->dailyAt('9:00'); // Adjust the time according to when you want to send reminders on the event start date

        // $schedule->call('App\Http\Controllers\EventController@sendReminderOneHourBeforeStartTime')
        // ->everyMinute();
        // ->hourly(); // Will check for events every hour
        
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
