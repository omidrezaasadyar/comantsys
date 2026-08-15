<?php

namespace App\Filament\Resources\PortalRequests\Schemas;

use App\Models\PortalRequest;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
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
}
