<?php

namespace App\Filament\Portal\Resources\PortalRequests\Pages;

use App\Filament\Concerns\RecordValueHelpers;
use App\Filament\Portal\Resources\PortalRequests\PortalRequestResource;
use App\Models\PortalRequest;
use App\Models\PortalRequestMessage;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * The customer's read-only view of their own request.
 *
 * ── Why a page-specific blade rather than HasRecordPageLayout ──
 * The layout mechanism is the project's usual one: `content()` — ViewRecord's
 * content slot — is replaced by a single `Schemas\Components\View`, so nothing
 * renders except our blade, and the default page header is suppressed by
 * emptying heading/subheading/breadcrumbs/header-actions (pages/page.blade.php
 * only emits `<x-filament-panels::header>` when one of them is filled). What
 * differs is the vocabulary: the shared trait speaks header-band / stat-cards /
 * detail-cards, and this screen's approved design is a status halo + icon-chip
 * grid + response card, which is not that shape. So it borrows the mechanism
 * and brings its own blade + view data instead of bending the shared array.
 *
 * ── Owner scope ──
 * Nothing is enforced here on purpose. ViewRecord::resolveRecord() calls
 * `Resource::resolveRecordRouteBinding()`, which builds off
 * `getRecordRouteBindingEloquentQuery()` → `static::getEloquentQuery()`
 * (Resources/Resource/Concerns/HasRoutes.php:38-53). The portal resource
 * overrides that with `->where('user_id', auth()->id())`, so a tampered id
 * simply finds no row and Filament throws ModelNotFoundException → 404. Adding
 * a second check here would only create a place for the two to disagree.
 *
 * ── Read-only ──
 * There is no form, no action and no writable component on this page: the whole
 * content slot is one View component, which holds no state and dehydrates
 * nothing. PIECE 1 is presentation only — no thread, no reply box.
 */
class ViewPortalRequest extends ViewRecord
{
    use RecordValueHelpers;

    protected static string $resource = PortalRequestResource::class;

    /** Tones a halo / chip / pill may carry — whitelisted so only a known class suffix reaches the blade. */
    protected const TONES = ['danger', 'warning', 'success', 'info', 'gray'];

    /**
     * Icon per view state. Presentation, so it lives here and not on the model.
     *
     * Heroicons ships no shield-question and no shield-x, so the two nearest
     * members of the same family stand in: ShieldExclamation for pending
     * (a shield still asking for attention) and XCircle for rejected.
     */
    protected const STATE_ICONS = [
        'rejected' => Heroicon::OutlinedXCircle,
        'revision' => Heroicon::OutlinedPencilSquare,
        'verified' => Heroicon::OutlinedShieldCheck,
        'pending' => Heroicon::OutlinedShieldExclamation,
    ];

    protected string $placeholder = '—';

    /** portal_request_messages.sender for a message written on this screen. */
    public const SENDER_CUSTOMER = 'customer';

    /** Sender whose presence opens the reply box (admin-initiated rule). */
    public const SENDER_ADMIN = 'admin';

    /**
     * Statuses after which the conversation is over. Replying to a request that
     * is already rejected or completed would put a message somewhere nobody is
     * looking, so the box closes instead.
     */
    public const CLOSED_STATUSES = ['rejected', 'completed'];

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.portal.request-view')
                ->viewData(fn (): array => $this->requestViewData()),
        ]);
    }

    // ── Default page header suppression — the blade renders its own back link ──

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    /**
     * @return array<string>
     */
    public function getBreadcrumbs(): array
    {
        return [];
    }

    /**
     * @return array<\Filament\Actions\Action>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    // ── Conversation ───────────────────────────────────────────────────────

    /**
     * May the customer post a reply right now?
     *
     * Two conditions, both required:
     *
     *   a) Staff must have written at least once. The conversation is
     *      ADMIN-INITIATED — a customer cannot open a thread out of nowhere,
     *      because a request nobody has picked up yet has no one listening.
     *   b) The request must not be in a final state. rejected/completed means
     *      the file is closed.
     *
     * This is the single source of truth for the gate: the blade asks it to
     * decide what to render, `replyAction()` asks it to decide visibility, and
     * the action's own closure asks it AGAIN before writing (see below).
     */
    protected function canReply(): bool
    {
        $record = $this->getRecord();

        if (in_array($record->request_status, self::CLOSED_STATUSES, true)) {
            return false;
        }

        return $record->messages()
            ->where('sender', self::SENDER_ADMIN)
            ->exists();
    }

    /**
     * Which note to show in place of the reply box. Two different situations
     * deserve two different sentences: "nothing needed from you yet" is not the
     * same message as "this conversation is closed".
     */
    protected function replyNote(): string
    {
        return in_array($this->getRecord()->request_status, self::CLOSED_STATUSES, true)
            ? __('portal_requests.view.reply_closed')
            : __('portal_requests.view.reply_awaiting');
    }

    /**
     * The reply box. Rendered inline by the blade as `$this->replyAction`, the
     * same dynamic-property resolution DatabaseBackupWidget relies on
     * (Schemas\Concerns\ResolvesDynamicLivewireProperties::__get). The modal
     * itself is emitted by the panel's own page layout — components/page/
     * index.blade.php:135 already prints <x-filament-actions::modals />, so the
     * blade must NOT print a second one.
     *
     * ── Why the closure re-checks everything ──
     * `->visible()` governs the BUTTON, not the endpoint. Filament's
     * mountAction() rejects a disabled action but does not test isVisible()
     * (Actions/Concerns/InteractsWithActions.php:112-135), so a hidden action
     * can still be mounted by a hand-made Livewire call. The gate and the
     * ownership check therefore run inside the closure, where they actually
     * guard the write.
     */
    public function replyAction(): Action
    {
        return Action::make('reply')
            ->label(__('portal_requests.view.reply'))
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->modalHeading(__('portal_requests.view.reply'))
            ->modalSubmitActionLabel(__('portal_requests.view.reply_submit'))
            ->visible(fn (): bool => $this->canReply())
            ->schema([
                Textarea::make('body')
                    ->label(__('portal_requests.view.reply_body'))
                    ->rows(5)
                    ->required()
                    ->maxLength(5000),
            ])
            ->action(function (array $data): void {
                $record = $this->getRecord();

                // Belt and braces over the resource's owner scope: that scope
                // is what resolved the record at mount, and #[Locked] stops the
                // id being swapped afterwards, but a write is worth checking at
                // the point of writing.
                abort_unless($record->user_id === auth()->id(), 403);

                abort_unless($this->canReply(), 403);

                $record->messages()->create([
                    'sender' => self::SENDER_CUSTOMER,
                    'body' => $data['body'],
                ]);

                // Drop the cached relation so the thread below re-queries and
                // the new message shows immediately.
                $record->unsetRelation('messages');

                Notification::make()
                    ->title(__('portal_requests.view.reply_sent'))
                    ->success()
                    ->send();
            });
    }

    /**
     * The thread, oldest → newest (the ordering lives on the relation, so every
     * reader gets the same order). Read-only: this builds display rows and
     * nothing on this page can edit or delete an existing message.
     *
     * @return array<int, array<string, string>>
     */
    protected function conversationMessages(PortalRequest $request): array
    {
        return $request->messages
            ->map(fn (PortalRequestMessage $message): array => [
                // 'admin' | 'customer' — a class suffix in the blade, so it is
                // derived from the model helpers rather than passed through.
                'side' => $message->isFromAdmin() ? 'admin' : 'customer',
                'who' => $message->isFromAdmin()
                    ? __('portal_requests.view.sender_admin')
                    : __('portal_requests.view.sender_you'),
                'time' => $this->toJalali($message->created_at, withTime: true) ?? $this->placeholder,
                'body' => (string) $message->body,
            ])
            ->all();
    }

    // ── View data ──────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    protected function requestViewData(): array
    {
        /** @var PortalRequest $request */
        $request = $this->getRecord();

        $state = $request->viewState();

        return [
            'halo' => [
                'tone' => $this->tone(PortalRequest::viewStateColors()[$state] ?? null),
                'icon' => self::STATE_ICONS[$state] ?? Heroicon::OutlinedClock,
                'title' => PortalRequest::viewStates()[$state] ?? '',
                'subtitle' => PortalRequest::viewStateDescriptions()[$state] ?? '',
                // Label only: the pill takes the halo's own tone, so the whole
                // box reads as one state. The request status keeps its own
                // colour in its info box further down.
                'pill' => PortalRequest::requestStatuses()[$request->request_status]
                    ?? $request->request_status,
            ],

            'boxes' => $this->boxes($request),

            // Only when staff actually wrote something — an empty card would
            // read as "answered" when nothing has been answered.
            'response' => filled($request->admin_response)
                ? [
                    'heading' => __('portal_requests.view.official_response'),
                    'body' => $request->admin_response,
                ]
                : null,

            'details' => [
                'heading' => __('portal_requests.view.details'),
                'subject_label' => __('portal_requests.field.subject'),
                'subject' => $this->value($request->subject),
                'description_label' => __('portal_requests.field.description'),
                'description' => $this->value($request->description),
                'attachments_label' => __('portal_requests.field.attachments'),
                'attachments' => $this->attachments($request),
                'attachments_empty' => __('portal_requests.view.no_attachments'),
            ],

            'conversation' => [
                'heading' => __('portal_requests.view.conversation'),
                'messages' => $this->conversationMessages($request),
                'empty' => __('portal_requests.view.conversation_empty'),
                'can_reply' => $this->canReply(),
                'note' => $this->replyNote(),
            ],

            // Same gate as the edit page itself — asked, not re-implemented, so
            // the button cannot appear in a state the page would refuse. The
            // page enforces it again server-side; this only decides the link.
            'edit' => EditPortalRequest::canAccess(['record' => $request])
                ? [
                    'label' => __('portal_requests.view.edit_request'),
                    'hint' => __('portal_requests.view.edit_hint'),
                    'url' => EditPortalRequest::getUrl(['record' => $request]),
                ]
                : null,

            'back' => [
                'label' => __('portal_requests.view.back_to_list'),
                'url' => PortalRequestResource::getUrl('index'),
            ],
        ];
    }

    /**
     * The eight summary chips, in the approved reading order.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function boxes(PortalRequest $request): array
    {
        return [
            [
                'icon' => Heroicon::OutlinedHashtag,
                'label' => __('portal_requests.field.request_number'),
                'value' => $this->value($request->request_number),
                'ltr' => true,
                'tone' => null,
            ],
            [
                'icon' => Heroicon::OutlinedUser,
                'label' => __('portal_requests.field.requester_name'),
                'value' => $this->value($request->requester_name),
                'ltr' => false,
                'tone' => null,
            ],
            [
                'icon' => Heroicon::OutlinedBuildingOffice2,
                'label' => __('portal_requests.field.company'),
                'value' => $this->value($request->company),
                'ltr' => false,
                'tone' => null,
            ],
            [
                'icon' => Heroicon::OutlinedUserCircle,
                'label' => __('portal_requests.field.related_person'),
                'value' => $this->value($request->related_person),
                'ltr' => false,
                'tone' => null,
            ],
            [
                'icon' => Heroicon::OutlinedCalendarDays,
                'label' => __('portal_requests.view.submitted_at'),
                // Jalali for now — the portal-locale step is deferred, so this
                // matches the rest of the codebase rather than Company.locale.
                'value' => $this->value($this->toJalali($request->created_at, withTime: true)),
                'ltr' => true,
                'tone' => null,
            ],
            [
                'icon' => Heroicon::OutlinedArrowPath,
                'label' => __('portal_requests.view.updated_at'),
                'value' => $this->value($this->toJalali($request->updated_at, withTime: true)),
                'ltr' => true,
                'tone' => null,
            ],
            [
                'icon' => Heroicon::OutlinedShieldCheck,
                'label' => __('portal_requests.field.validation_status'),
                'value' => PortalRequest::validationStatuses()[$request->validation_status]
                    ?? $this->value($request->validation_status),
                'ltr' => false,
                'tone' => $this->tone(
                    PortalRequest::validationStatusColors()[$request->validation_status] ?? null,
                ),
            ],
            [
                'icon' => Heroicon::OutlinedClipboardDocumentCheck,
                'label' => __('portal_requests.field.request_status'),
                'value' => PortalRequest::requestStatuses()[$request->request_status]
                    ?? $this->value($request->request_status),
                'ltr' => false,
                'tone' => $this->tone(
                    PortalRequest::requestStatusColors()[$request->request_status] ?? null,
                ),
            ],
        ];
    }

    /**
     * The customer's OWN files only (source='customer'), served through the
     * guarded download route — the same auth-gated controller the admin screen
     * links to, which 404s a portal user asking for someone else's attachment.
     * Staff response files are a separate concern and are not listed here.
     *
     * @return array<int, array<string, string>>
     */
    protected function attachments(PortalRequest $request): array
    {
        return $request
            ->attachments()
            ->where('source', 'customer')
            ->orderBy('id')
            ->get()
            ->map(fn ($attachment): array => [
                'label' => filled($attachment->title)
                    ? $attachment->title
                    : basename((string) $attachment->file_path),
                'url' => route('portal-request-attachment.download', $attachment),
            ])
            ->all();
    }

    protected function value(mixed $value): string
    {
        return filled($value) ? (string) $value : $this->placeholder;
    }

    /**
     * A tone reaches the browser as a class suffix, so only known names pass.
     */
    protected function tone(?string $tone): ?string
    {
        return in_array($tone, self::TONES, true) ? $tone : null;
    }
}
