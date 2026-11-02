<?php

use App\Console\Commands\ProsesOtomatisAbsensi;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Task 12 — Job otomatis absensi tiap menit (idempoten via cache key per tanggal)
Schedule::command(ProsesOtomatisAbsensi::class)
    ->everyMinute()
    ->withoutOverlapping();
