<?php

use App\Support\AutoSyncWindow;
use App\Support\SyncWriterWindow;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SQLite has a single write lock, so these three must not write at once.
// See App\Support\SyncWriterWindow. Off-the-hour minutes also keep us out of
// the :00 / :30 cron thundering herd on shared hosting.
// Night window only (22:00–06:00 Asia/Baghdad). Daytime runs were locking
// SQLite while the app was in use. Manual "sync now" does not go through here.
Schedule::command('vinstack:sync')
    ->hourlyAt(7)
    ->name('vinstack-auto-sync')
    ->withoutOverlapping()
    ->when(fn () => AutoSyncWindow::isOpen())
    ->before(fn () => SyncWriterWindow::open('vinstack'))
    ->after(fn () => SyncWriterWindow::close());

Schedule::command('autoshipper:sync')
    ->hourlyAt(37)
    ->name('autoshipper-auto-sync')
    ->withoutOverlapping()
    ->when(fn () => AutoSyncWindow::isOpen())
    ->before(fn () => SyncWriterWindow::open('autoshipper'))
    ->after(fn () => SyncWriterWindow::close());

Schedule::command('image-transfers:process')
    ->everyMinute()
    ->name('image-transfers-process')
    ->withoutOverlapping()
    // Skips rather than waits: the next tick is only 60s away, whereas a
    // skipped sync would have to wait a full hour.
    ->skip(fn () => SyncWriterWindow::isOpen());
