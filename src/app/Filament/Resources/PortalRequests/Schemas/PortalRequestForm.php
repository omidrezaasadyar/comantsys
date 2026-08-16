<?php

namespace App\Filament\Resources\PortalRequests\Schemas;

use App\Models\PortalRequest;
use App\Models\PortalRequestMessage;
use Ariaieboy\Jalali\Jalali;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Support\HtmlString;

/**
 * The admin review/respond form.
 *
 * Two zones, and the split is the whole point of the screen:
 *
 *   Zone A — what the customer submitted. Read-only. Every field carries
 *            ->disabled(), which in Filament also means NOT dehydrated, so
 *            these columns never appear in $data and can never be overwritten
 *            by an operator, tampered request, or future refactor.
 *   Zone B — the four operator-owned fields. The only editable things here.
 *
 * The attachment source split runs down the same line: customer files
 * (source='customer') render as a read-only download list in Zone A; the
 * FileUpload in Zone B reads and writes ONLY source='admin' rows
 * (see EditPortalRequest). Neither half can see or clobber the other's files.
 */
class PortalRequestForm
{
    /**
     * Form key for the admin response files. NOT a column on portal_requests —
     * EditPortalRequest lifts it out before save and writes the related rows.
     */
    public const ADMIN_ATTACHMENTS_KEY = 'admin_attachments';

    /** Values of portal_request_attachments.source. */
    public const SOURCE_CUSTOMER = 'customer';

    public const SOURCE_ADMIN = 'admin';

    /** Private disk, same as every other attachment in the project. */
    public const DISK = 'local';

    /**
     * Own directory, parallel to 'portal-request-attachments' /
     * 'inquiry-attachments' / 'sourcing-request-attachments'.
     *
     * Deliberately NOT the customers' directory: both sides use
     * ->preserveFilenames(), so an operator uploading a response named
     * "invoice.pdf" into the customers' folder would silently overwrite the
     * customer's submitted "invoice.pdf". Separate folders make that impossible.
     */
    public const ADMIN_DIRECTORY = 'portal-request-responses';

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ══ ZONE A — customer submission (READ-ONLY) ══
                Section::make(__('portal_requests.admin.section.submission'))
                    ->description(__('portal_requests.admin.hint.submission'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('request_number')
                            ->label(__('portal_requests.field.request_number'))
                            ->disabled(),

                        // شمسی روی صفحه، میلادی در دیتابیس — همان قرارداد بقیهٔ فرم‌ها
                        DatePicker::make('request_date')
                            ->label(__('portal_requests.field.request_date'))
                            ->jalali()
                            ->disabled(),

                        TextInput::make('requester_name')
                            ->label(__('portal_requests.field.requester_name'))
                            ->disabled(),

                        TextInput::make('company')
                            ->label(__('portal_requests.field.company'))
                            ->disabled(),

                        TextInput::make('email')
                            ->label(__('portal_requests.field.email'))
                            ->disabled(),

                        TextInput::make('phone')
                            ->label(__('portal_requests.field.phone'))
                            ->disabled(),

                        TextInput::make('related_person')
                            ->label(__('portal_requests.field.related_person'))
                            ->placeholder('—')
                            ->disabled(),

                        TextInput::make('subject')
                            ->label(__('portal_requests.field.subject'))
                            ->columnSpanFull()
                            ->disabled(),

                        Textarea::make('description')
                            ->label(__('portal_requests.field.description'))
                            ->rows(6)
                            ->columnSpanFull()
                            ->disabled(),
                    ]),

                // ══ ZONE A (cont.) — customer files, read-only download list ══
                Section::make(__('portal_requests.admin.section.customer_files'))
                    ->columnSpanFull()
                    ->schema([
                        // Not a FileUpload on purpose: an editable component here
                        // would let an operator delete the customer's evidence.
                        Placeholder::make('customer_attachments')
                            ->hiddenLabel()
                            ->content(fn (?PortalRequest $record): HtmlString => new HtmlString(
                                static::customerFilesHtml($record),
                            )),
                    ]),

                // ══ ZONE B — operator fields (EDITABLE) ══
                Section::make(__('portal_requests.admin.section.review'))
                    ->description(__('portal_requests.admin.hint.review'))
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        // Options come from the model, the single source of truth
                        // the portal table and badges already read.
                        Select::make('validation_status')
                            ->label(__('portal_requests.field.validation_status'))
                            ->options(PortalRequest::validationStatuses())
                            ->native(false)
                            ->required(),

                        Select::make('request_status')
                            ->label(__('portal_requests.field.request_status'))
                            ->options(PortalRequest::requestStatuses())
                            ->native(false)
                            ->required(),

                        Textarea::make('admin_response')
                            ->label(__('portal_requests.admin.field.admin_response'))
                            ->helperText(__('portal_requests.admin.help.admin_response'))
                            ->rows(6)
                            ->columnSpanFull(),

                        // Writes source='admin' rows only — see EditPortalRequest.
                        FileUpload::make(self::ADMIN_ATTACHMENTS_KEY)
                            ->label(__('portal_requests.admin.field.admin_attachments'))
                            ->helperText(__('portal_requests.admin.help.admin_attachments'))
                            ->multiple()
                            ->maxFiles(10)
                            ->disk(self::DISK)
                            ->directory(self::ADMIN_DIRECTORY)
                            ->preserveFilenames()
                            // KILOBYTES (emits Laravel's "max:{n}" file rule).
                            ->maxSize(10240)
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->columnSpanFull(),
                    ]),

                // ══ ZONE C — conversation (READ-ONLY LOG) ══
                // Immutable by construction: a Placeholder renders HTML and
                // holds no state, so there is nothing here to edit, delete or
                // dehydrate. New messages arrive only through the page's
                // sendMessage action, which appends — see EditPortalRequest.
                Section::make(__('portal_requests.admin.section.conversation'))
                    ->description(__('portal_requests.admin.hint.conversation'))
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('conversation')
                            ->hiddenLabel()
                            ->content(fn (?PortalRequest $record): HtmlString => new HtmlString(
                                static::conversationHtml($record),
                            )),
                    ]),
            ]);
    }

    /**
     * Read-only download list for the CUSTOMER's files.
     *
     * Filtered to source='customer', so admin response files never leak into
     * this list. Links go through the auth-gated download route, the same way
     * inquiry and sourcing attachments are served off the private disk — no
     * public URL is ever produced.
     */
    protected static function customerFilesHtml(?PortalRequest $record): string
    {
        $attachments = $record
            ?->attachments()
            ->where('source', self::SOURCE_CUSTOMER)
            ->orderBy('id')
            ->get();

        if (blank($attachments)) {
            return '<span class="text-sm text-gray-500 dark:text-gray-400">'
                . e(__('portal_requests.admin.empty.customer_attachments'))
                . '</span>';
        }

        $items = $attachments
            ->map(function ($attachment): string {
                $label = filled($attachment->title)
                    ? $attachment->title
                    : basename((string) $attachment->file_path);

                $url = route('portal-request-attachment.download', $attachment);

                return '<li>'
                    . '<a href="' . e($url) . '" target="_blank" rel="noopener noreferrer"'
                    . ' class="text-primary-600 hover:underline dark:text-primary-400">'
                    . e($label)
                    . '</a>'
                    . '</li>';
            })
            ->implode('');

        return '<ul class="list-disc space-y-1 ps-5 text-sm">' . $items . '</ul>';
    }

    /**
     * The conversation, oldest → newest (the ordering lives on the relation,
     * so every reader gets the same thread order).
     *
     * Deliberately plain HTML, like customerFilesHtml() above: a Placeholder
     * cannot be edited or dehydrated, which is exactly the guarantee this log
     * needs — an operator can add to the thread but never rewrite it.
     *
     * Styling goes through a scoped <style> block with prm-* class names rather
     * than Tailwind utilities. Tailwind v4 scans source files to decide which
     * utilities to compile, and classes that exist only inside a PHP string are
     * not guaranteed to survive that scan (CLAUDE.md §6) — hand-written CSS is
     * immune. Dark mode keys off Filament's `.dark` ancestor class.
     */
    protected static function conversationHtml(?PortalRequest $record): string
    {
        $messages = $record?->messages;

        if (blank($messages)) {
            return '<span class="text-sm text-gray-500 dark:text-gray-400">'
                . e(__('portal_requests.admin.empty.conversation'))
                . '</span>';
        }

        $rows = $messages
            ->map(function (PortalRequestMessage $message): string {
                $isAdmin = $message->isFromAdmin();

                $side = $isAdmin ? 'admin' : 'customer';

                $who = $isAdmin
                    ? __('portal_requests.admin.sender.admin')
                    : __('portal_requests.admin.sender.customer');

                // nl2br AFTER e(): escape first, then turn the newlines the
                // operator actually typed into markup. The reverse order would
                // let the escaping eat the <br> tags.
                $body = nl2br(e((string) $message->body));

                return '<li class="prm-msg prm-' . $side . '">'
                    . '<div class="prm-meta">'
                    . '<span class="prm-who">' . e($who) . '</span>'
                    . '<span class="prm-time">' . e(static::jalaliMoment($message->created_at)) . '</span>'
                    . '</div>'
                    . '<div class="prm-body">' . $body . '</div>'
                    . '</li>';
            })
            ->implode('');

        return static::conversationStyles() . '<ul class="prm-thread">' . $rows . '</ul>';
    }

    /**
     * Jalali date+time for display — the same conversion the project's
     * `jalaliDateTime()` table macro and RecordValueHelpers::toJalali() use, so
     * a message stamp reads identically to every other timestamp in the panel.
     */
    protected static function jalaliMoment(mixed $value): string
    {
        if (blank($value)) {
            return '—';
        }

        return Jalali::fromCarbon(
            Carbon::parse($value)->setTimezone(FilamentTimezone::get()),
        )->format('Y/m/d H:i');
    }

    /**
     * Scoped styles for the thread. Emitted once per render alongside the list.
     */
    protected static function conversationStyles(): string
    {
        return <<<'CSS'
            <style>
                .prm-thread { display: flex; flex-direction: column; gap: .75rem; margin: 0; padding: 0; list-style: none; }
                .prm-msg { border: 1px solid rgb(228 228 231); border-radius: .75rem; padding: .625rem .875rem; max-width: 46rem; }
                .prm-customer { background-color: rgb(244 244 245); margin-inline-end: auto; }
                .prm-admin { background-color: rgb(239 246 255); border-color: rgb(191 219 254); margin-inline-start: auto; }
                .prm-meta { display: flex; gap: .5rem; align-items: baseline; justify-content: space-between; margin-bottom: .25rem; }
                .prm-who { font-size: .75rem; font-weight: 600; color: rgb(63 63 70); }
                .prm-time { font-size: .6875rem; color: rgb(113 113 122); font-variant-numeric: tabular-nums; }
                .prm-body { font-size: .875rem; line-height: 1.6; color: rgb(39 39 42); white-space: normal; word-break: break-word; }
                .dark .prm-msg { border-color: rgb(63 63 70); }
                .dark .prm-customer { background-color: rgb(39 39 42); }
                .dark .prm-admin { background-color: rgb(30 41 59); border-color: rgb(51 65 85); }
                .dark .prm-who { color: rgb(228 228 231); }
                .dark .prm-time { color: rgb(161 161 170); }
                .dark .prm-body { color: rgb(228 228 231); }
            </style>
            CSS;
    }
}
