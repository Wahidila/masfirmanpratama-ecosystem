<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Release komisi cooling -> available setiap hari
Schedule::command('commissions:release')->daily();

// Finalisasi event yang sudah melewati end_date
Schedule::command('events:finalize')->daily();
