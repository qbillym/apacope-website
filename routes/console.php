<?php
use Illuminate\Foundation\Inspiring;use Illuminate\Support\Facades\{Artisan,Schedule};
Artisan::command('inspire',function(){$this->comment(Inspiring::quote());})->purpose('Display an inspiring quote');
Schedule::command('apacope:backup')->dailyAt('02:00');
Schedule::command('app:assign-zero-marks-for-missed-deadlines')->dailyAt('03:00');
