<?php

namespace App\Filament\Portal\Resources\PortalRequests\Schemas;

use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class PortalRequestForm
{
    /** Combined ceiling for all attachments on one request. */
    public const MAX_TOTAL_UPLOAD_BYTES = 25 * 1024 * 1024;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ── ۱. جعبهٔ قوانین (فقط اطلاع‌رسانی) ──
                Section::make(__('portal_requests.section.rules'))
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('rules')
                            ->hiddenLabel()
                            ->content(fn (): HtmlString => new HtmlString(static::rulesHtml())),
                    ]),

                // ── ۲. اطلاعات درخواست‌دهنده ──
                Section::make(__('portal_requests.section.requester'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('requester_name')
                            ->label(__('portal_requests.field.requester_name'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('company')
                            ->label(__('portal_requests.field.company'))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label(__('portal_requests.field.email'))
                            ->email()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label(__('portal_requests.field.phone'))
                            ->tel()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('related_person')
                            ->label(__('portal_requests.field.related_person'))
                            ->helperText(__('portal_requests.help.related_person'))
                            ->maxLength(255),
                    ]),

                // ── ۳. شرح درخواست ──
                Section::make(__('portal_requests.section.request'))
                    ->columns(1)
                    ->schema([
                        TextInput::make('subject')
                            ->label(__('portal_requests.field.subject'))
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->label(__('portal_requests.field.description'))
                            ->required()
                            ->rows(6),
                    ]),

                // ── ۴. پیوست‌ها ──
                Section::make(__('portal_requests.section.attachments'))
                    ->columnSpanFull()
                    ->schema([
                        // دیسک خصوصی (local = storage/app/private)، مثل سایر پیوست‌های پروژه.
                        FileUpload::make('attachments')
                            ->label(__('portal_requests.field.attachments'))
                            ->helperText(__('portal_requests.help.attachments'))
                            ->multiple()
                            ->maxFiles(10)
                            ->required()
                            ->disk('local')
                            ->directory('portal-request-attachments')
                            ->preserveFilenames()
                            // Per-file cap, in KILOBYTES (maxSize() emits Laravel's
                            // "max:{n}" file rule). 5120 KB = 5 MB. This is applied
                            // to each file; the closure below caps the combined total.
                            ->maxSize(5120)
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->rule(static::totalSizeRule()),
                    ]),

                // ── ۵. پذیرش شرایط ──
                Section::make(__('portal_requests.section.consent'))
                    ->columnSpanFull()
                    ->schema([
                        Checkbox::make('terms_accepted')
                            ->label(__('portal_requests.terms.label'))
                            ->helperText(__('portal_requests.terms.helper'))
                            ->accepted()
                            ->required(),
                    ]),
            ]);
    }

    /**
     * Caps the COMBINED size of all attachments. Filament's ->maxSize() is
     * per-file, so ten 10 MB files would otherwise sail through.
     *
     * Two vendor behaviours shape this, both verified in the v5.6.7 source:
     *
     * 1. CanBeValidated::getValidationRules() runs `$rule = $this->evaluate($rule)`
     *    on whatever ->rule() was given, so a bare Laravel closure rule would be
     *    *invoked* during rule collection instead of reaching the validator. The
     *    real rule therefore has to be RETURNED from an outer closure.
     *
     * 2. BaseFileUpload::getValidationRules() sorts inherited rules with
     *    isArrayValidationRule(), which returns false for anything that is not a
     *    string — so this closure is applied to EACH FILE, never to the array.
     *    $value is a single file, which is useless for a total. We therefore close
     *    over the injected $state (the whole set) instead of reading $value.
     *    Filament surfaces only the first sub-validator error, so the message is
     *    reported once even though the rule runs per file.
     */
    protected static function totalSizeRule(): Closure
    {
        return static fn (mixed $state): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($state): void {
            $total = 0;

            foreach (Arr::wrap($state) as $file) {
                // Pending uploads are TemporaryUploadedFile; previously stored
                // values arrive as path strings and are not re-counted.
                if (is_object($file) && method_exists($file, 'getSize')) {
                    $total += (int) $file->getSize();
                }
            }

            if ($total > self::MAX_TOTAL_UPLOAD_BYTES) {
                $fail(__('portal_requests.validation.attachments_total_size', [
                    'size' => number_format($total / 1024 / 1024, 1),
                ]));
            }
        };
    }

    /**
     * The rules box. Built from lang keys so nothing is hardcoded.
     */
    protected static function rulesHtml(): string
    {
        $items = (array) __('portal_requests.rules.items');

        $list = collect($items)
            ->map(fn (string $item): string => '<li>' . e($item) . '</li>')
            ->implode('');

        return '<p class="mb-2">' . e(__('portal_requests.rules.intro')) . '</p>'
            . '<ul class="list-disc space-y-1 ps-5">' . $list . '</ul>';
    }
}
