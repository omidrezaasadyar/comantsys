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
    // ── Customer view page ──
    // view_state = the single state derived from the two status columns; the
    // priority chain lives in PortalRequest::viewState().
    'view_state' => [
        'rejected' => [
            'title'       => 'Request rejected',
            'description' => 'This request did not pass the initial review. Please contact your account manager to follow up.',
        ],
        'revision' => [
            'title'       => 'Your revision is needed',
            'description' => 'Staff have asked you to revise this request. See the official response for details.',
        ],
        'verified' => [
            'title'       => 'Request verified',
            'description' => 'The request details have been verified and the request is being processed.',
        ],
        'pending' => [
            'title'       => 'Awaiting review',
            'description' => 'Your request has been submitted and is queued for review.',
        ],
    ],

    'view' => [
        'back_to_list'      => 'Back to requests',
        'submitted_at'      => 'Submitted',
        'updated_at'        => 'Last updated',
        'official_response' => 'Official response',
        'details'           => 'Request details',
        'no_attachments'    => 'No files attached.',

        // ── Conversation ──
        'conversation'       => 'Conversation',
        'conversation_empty' => 'No messages exchanged yet.',
        'sender_admin'       => 'EIS',
        'sender_you'         => 'You',
        'reply'              => 'Reply to staff',
        'reply_submit'       => 'Send',
        'reply_body'         => 'Message',
        'reply_sent'         => 'Your message has been sent.',
        // Staff have not written yet — they start the conversation.
        'reply_awaiting'     => 'No reply is needed from you right now. If staff send a message, you will be able to answer here.',
        // The file is closed (rejected or completed).
        'reply_closed'       => 'This request is closed; no new messages can be sent.',
    ],

    'request_status' => [
        'received'       => 'Received',
        'under_review'   => 'Under review',
        'needs_revision' => 'Needs revision',
        'queued'         => 'Queued',
        'rejected'       => 'Rejected',
        'completed'      => 'Completed',
    ],

    // ── Admin side (internal review desk) ──
    // The keys above are shared (the model and both panels read them); this
    // sub-tree belongs to the admin panel only, so the portal labels stay put.
    'admin' => [

        // All three deliberately identical: sidebar (nav), list-page title
        // (plural) and breadcrumb/singular (model) should read the same, so the
        // menu and the page never show two different names for one thing.
        'nav'    => 'Request Management',
        'plural' => 'Request Management',
        'model'  => 'Request Management',
        'review' => 'Review',

        'section' => [
            'submission'     => 'Submitted by the customer',
            'customer_files' => 'Customer attachments',
            'review'         => 'Review and response',
            'conversation'   => 'Conversation with the customer',
        ],

        'hint' => [
            'submission'   => 'Entered by the customer; not editable here.',
            'review'       => 'Only this section is filled in by staff; the result is shown in the customer portal.',
            'conversation' => 'The message history is read-only and is never edited or deleted; use the "Send message" button at the top of the page to add one.',
        ],

        'field' => [
            'admin_response'    => 'Staff response',
            'admin_attachments' => 'Response attachments',
            'message_body'      => 'Message',
            'ask_revision'      => 'Ask customer to revise (unlocks editing)',
        ],

        'help' => [
            'admin_response'    => 'The text the customer sees in their portal.',
            'admin_attachments' => 'Files sent to the customer with the response. Up to 10 files, 10 MB each.',
            'ask_revision'      => 'Ticking this moves the request status to "Needs revision". Unticking it does not undo that.',
        ],

        'empty' => [
            'customer_attachments' => 'The customer attached no files.',
            'conversation'         => 'No messages exchanged yet.',
        ],

        'sender' => [
            'admin'    => 'Staff',
            'customer' => 'Customer',
        ],

        'action' => [
            'send_message'        => 'Send message',
            'send_message_submit' => 'Send',
        ],

        'notify' => [
            'message_sent' => 'Message posted.',
        ],
    ],

];
