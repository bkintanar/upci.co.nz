<?php

return [
    // Where new ABC enrolment notifications go. Empty means no email is sent;
    // submissions still land in the admin inbox. A placeholder until the
    // college confirms which address should receive them.
    'enrolment_notify_to' => env('ABC_ENROLMENT_NOTIFY_EMAIL'),
];
