<?php

return [
    // Keep delivery durable even when the application's default queue is sync.
    'queue_connection' => env('INQUIRY_NOTIFICATION_QUEUE', 'database'),
];
