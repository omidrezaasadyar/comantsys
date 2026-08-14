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
        'requester_name'    => 'Requester name',
        'company'           => 'Company',
        'email'             => 'Email',
        'phone'             => 'Phone',
        'related_person'    => 'Related person',
        'description'       => 'Description',
        'attachments'       => 'Attachments',
    ],

    'create' => 'New request',

    // ── Form sections ──
    'section' => [
        'rules'       => 'Terms and submission rules',
        'requester'   => 'Requester details',
        'request'     => 'Request details',
        'attachments' => 'Documents and attachments',
        'consent'     => 'Consent',
    ],

    // ── Rules box shown at the top of the form ──
    'rules' => [
        'intro' => 'Please read the following before submitting a request:',
        'items' => [
            'accurate' => 'Contact details must be accurate and current; we reply through them.',
            'documents' => 'Documents must be legible and complete (up to 10 files, 25 MB in total).',
            'reviewed' => 'Every request is reviewed and the outcome is published in this portal.',
            'incomplete' => 'Incomplete requests, or those with inaccurate details, will be rejected.',
        ],
    ],

    // ── Consent ──
    'terms' => [
        'label'  => 'I have read the terms above and confirm the details I entered are accurate.',
        'helper' => 'Accepting this is required in order to submit.',
    ],

    'help' => [
        'attachments' => 'Up to 10 files. Their combined size must not exceed 25 MB.',
        'related_person' => 'If applicable, name the colleague or department involved.',
    ],

    'validation' => [
        'attachments_total_size' => 'The attachments total :size MB, which exceeds the 25 MB limit.',
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
