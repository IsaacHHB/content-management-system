<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('invites:prune')->dailyAt('00:10');
Schedule::command('contact:prune')->monthly();
Schedule::command('activitylog:clean --force')->weekly();

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
