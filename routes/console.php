<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('warehouse-photos:cleanup')->everyFiveMinutes()->withoutOverlapping()
    ->when(fn (): bool => (bool) config('filesystems.warehouse_direct_upload'));

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
