<?php

return [

    // ── برچسب‌های منبع (Resource) ──
    'model'  => 'استعلام',
    'plural' => 'استعلام‌ها',

    // ── دکمه‌ها / کنش‌ها ──
    'add_item'       => 'افزودن قلم',
    'add_attachment' => 'افزودن پیوست',
    'open'           => 'باز کردن',

    // ── سرفصل بخش‌ها (Sections) ──
    'section' => [
        'info'        => 'اطلاعات استعلام',
        'items'       => 'اقلام استعلام',
        'attachments' => 'پیوست‌ها',
    ],

    // ── برچسب فیلدها و ستون‌ها ──
    'field' => [
        'customer'         => 'مشتری',
        'inquiry_number'   => 'شمارهٔ استعلام',
        'inquiry_date'     => 'تاریخ استعلام',
        'response_date'    => 'تاریخ پاسخ',
        'status'           => 'وضعیت',
        'description'      => 'توضیحات',
        'item_description' => 'شرح قلم',
        'quantity'         => 'تعداد',
        'unit'             => 'واحد',
        'unit_other'       => 'واحد (سایر)',
        'item_notes'       => 'یادداشت',
        'items'            => 'اقلام',
        'attachment_title' => 'عنوان پیوست',
        'file'             => 'فایل',
        'created_at'       => 'تاریخ ایجاد',
        'updated_at'       => 'تاریخ به‌روزرسانی',
        'uploaded_at'      => 'تاریخ بارگذاری',
        'output_language'  => 'زبان خروجی',
    ],

    // ── متن راهنما (Helper text) ──
    'help' => [
        'inquiry_number' => 'شمارهٔ یکتای استعلام',
        'file'           => 'حداکثر حجم ۱۰ مگابایت — PDF، تصویر، Word یا Excel',
    ],

    // ── وضعیت‌ها (منبع یگانه: Inquiry::statuses) ──
    'status' => [
        'received'  => 'دریافت‌شده',
        'reviewing' => 'در حال بررسی',
        'approved'  => 'تأییدشده',
        'sent'      => 'ارسال‌شده',
        'delivered' => 'تحویل‌شده',
        'cancelled' => 'لغوشده',
    ],

    // ── واحدها (منبع یگانه: InquiryItem::units) ──
    'unit' => [
        'piece'        => 'عدد',
        'meter'        => 'متر',
        'kilogram'     => 'کیلوگرم',
        'liter'        => 'لیتر',
        'square_meter' => 'مترمربع',
        'other'        => 'سایر',
    ],

    // ── کنش‌های صفحه (تأمین‌یابی هوشمند) ──
    'action' => [
        'sourcing' => 'تأمین‌یابی هوشمند',
    ],

    // ── زبان خروجی عامل تأمین‌یابی ──
    'lang' => [
        'fa' => 'فارسی',
        'en' => 'English',
    ],

    // ── پیام‌های اعلان (Notification) ──
    'notify' => [
        'queued_title'     => 'تأمین‌یابی در صف اجرا قرار گرفت',
        'queued_body'      => 'این عملیات در پس‌زمینه اجرا می‌شود؛ نتیجه پس از تکمیل در دسترس خواهد بود.',
        'in_progress_title' => 'یک اجرای تأمین‌یابی در جریان است',
    ],

];
