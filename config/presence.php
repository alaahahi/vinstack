<?php

return [
    'online_threshold_minutes' => (int) env('ONLINE_THRESHOLD_MINUTES', 5),

    // Skip last_seen_at writes when the user was touched recently.
    // Frontend heartbeats every 60s; 90s halves SQLite write pressure while
    // staying well under the online threshold (default 5 minutes).
    'touch_throttle_seconds' => (int) env('PRESENCE_TOUCH_THROTTLE_SECONDS', 90),
];
