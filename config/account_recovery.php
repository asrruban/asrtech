<?php

return [
    // Queue account lookups to keep the public response independent of whether
    // an account exists. This connection must use a durable asynchronous driver.
    'queue_connection' => env('ACCOUNT_RECOVERY_QUEUE', 'database'),
];
