<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SQLite lock / busy event log (file-only)
    |--------------------------------------------------------------------------
    |
    | Records SQLITE_BUSY / "database is locked" events to JSONL files under
    | storage/logs/sqlite-locks/. Never writes to the application database.
    | Runtime enable/disable is a small JSON flag file (also no DB).
    |
    */

    'enabled' => (bool) env('SQLITE_LOCK_LOG_ENABLED', true),

    'log_path' => env('SQLITE_LOCK_LOG_PATH', storage_path('logs/sqlite-locks')),

    'state_path' => env(
        'SQLITE_LOCK_LOG_STATE_PATH',
        storage_path('app/sqlite-lock-log.json')
    ),

    'retention_days' => (int) env('SQLITE_LOCK_LOG_RETENTION_DAYS', 14),

    'max_recent_events' => (int) env('SQLITE_LOCK_LOG_RECENT', 30),

    'max_read_bytes' => (int) env('SQLITE_LOCK_LOG_MAX_READ_BYTES', 2_097_152),

];
