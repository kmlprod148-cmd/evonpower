<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Schedule Subscription Renewals
|--------------------------------------------------------------------------
|
| Schedule the subscription renewals command to run daily at midnight
|
*/
Schedule::command('subscriptions:renew')->dailyAt('00:00');
