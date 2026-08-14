<?php

// lang/fa/portal_requests.php
// برچسب‌های ماژول «درخواست‌های پورتال».
// منبع یگانهٔ برچسب‌ها؛ مدل PortalRequest از همین کلیدها استفاده می‌کند.

return [

    'nav'    => 'درخواست‌های من',
    'plural' => 'درخواست‌های من',

    // ── برچسب ستون‌ها/فیلدها ──
    'field' => [
        'request_number'    => 'شمارهٔ درخواست',
        'subject'           => 'موضوع',
        'validation_status' => 'وضعیت اعتبارسنجی',
        'request_status'    => 'وضعیت رسیدگی',
        'request_date'      => 'تاریخ درخواست',
    ],

    // ── وضعیت اعتبارسنجی (بررسی صحت اطلاعات درخواست‌دهنده) ──
    'validation_status' => [
        'pending'  => 'در انتظار بررسی',
        'verified' => 'تأییدشده',
        'rejected' => 'ردشده',
    ],

    // ── وضعیت رسیدگی به درخواست ──
    'request_status' => [
        'received'     => 'دریافت‌شده',
        'under_review' => 'در حال بررسی',
        'queued'       => 'در صف انجام',
    ],

];
