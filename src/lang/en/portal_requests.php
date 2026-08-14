<?php

// lang/en/portal_requests.php
// Labels for the portal-requests module. Mirrors lang/fa/portal_requests.php.

return [

    'nav'    => 'My requests',
    'plural' => 'My requests',

    // ── Column / field labels ──
    'field' => [
        'request_number'    => 'Request number',
        'subject'           => 'Subject',
        'validation_status' => 'Validation status',
        'request_status'    => 'Request status',
        'request_date'      => 'Request date',
    ],

    // ── Validation of the requester's details ──
    'validation_status' => [
        'pending'  => 'Pending',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
    ],

    // ── Progress of the request itself ──
    'request_status' => [
        'received'     => 'Received',
        'under_review' => 'Under review',
        'queued'       => 'Queued',
    ],

];
