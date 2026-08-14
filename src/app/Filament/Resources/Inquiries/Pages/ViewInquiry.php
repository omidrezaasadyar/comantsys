<?php

namespace App\Filament\Resources\Inquiries\Pages;

use App\Filament\Concerns\HasRecordPageLayout;
use App\Filament\Resources\Inquiries\InquiryResource;
use App\Models\Inquiry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewInquiry extends ViewRecord
{
    use HasRecordPageLayout;

    protected static string $resource = InquiryResource::class;

    /**
     * @return array<string, mixed>
     */
    protected function getRecordPageSchema(): array
    {
        /** @var Inquiry $inquiry */
        $inquiry = $this->getRecord();

        // Single source of truth for status label + colour, per the model.
        $status = Inquiry::statuses()[$inquiry->status] ?? $inquiry->status;
        $statusColor = Inquiry::statusColors()[$inquiry->status] ?? 'gray';

        $direction = filled($inquiry->direction)
            ? __('inquiries.direction.' . $inquiry->direction)
            : null;

        return [
            'header' => [
                'icon' => Heroicon::OutlinedDocumentMagnifyingGlass,
                'title' => $inquiry->inquiry_number,
                'badge' => filled($inquiry->status)
                    ? ['label' => $status, 'color' => $statusColor]
                    : null,
                'subtitle' => $this->joinFilled(
                    [__('inquiries.model'), $direction, $inquiry->customer?->name],
                    ' · ',
                ),
                'breadcrumbs' => [
                    ['label' => __('inquiries.plural'), 'url' => InquiryResource::getUrl('index')],
                    ['label' => $inquiry->inquiry_number, 'url' => null],
                ],
                'edit_url' => InquiryResource::canEdit($inquiry)
                    ? InquiryResource::getUrl('edit', ['record' => $inquiry])
                    : null,
            ],

            'stats' => [
                [
                    'icon' => Heroicon::OutlinedUserCircle,
                    'label' => __('inquiries.field.customer'),
                    'value' => $inquiry->customer?->name,
                    'glow' => 'sky',
                ],
                [
                    'icon' => Heroicon::OutlinedBuildingOffice2,
                    'label' => __('inquiries.field.company'),
                    'value' => $inquiry->company?->name,
                    'glow' => 'sky',
                ],
                [
                    'icon' => Heroicon::OutlinedCalendarDays,
                    'label' => __('inquiries.field.inquiry_date'),
                    'value' => $this->toJalali($inquiry->inquiry_date),
                    'ltr' => true,
                    'glow' => 'violet',
                ],
                [
                    'icon' => Heroicon::OutlinedCalendarDays,
                    'label' => __('inquiries.field.response_date'),
                    'value' => $this->toJalali($inquiry->response_date),
                    'ltr' => true,
                    'glow' => 'violet',
                ],
            ],

            // Items were a RepeatableEntry in the infolist (not a relation
            // manager), so they become the full-width table.
            'items' => [
                'heading' => __('inquiries.section.items'),
                'empty' => 'قلمی برای این استعلام ثبت نشده است.',
                'columns' => [
                    ['label' => __('inquiries.field.item_description'), 'align' => 'start'],
                    ['label' => __('inquiries.field.quantity'), 'align' => 'center', 'ltr' => true],
                    ['label' => __('inquiries.field.unit'), 'align' => 'center'],
                    ['label' => __('inquiries.field.item_notes'), 'align' => 'start'],
                ],
                'rows' => $inquiry->items
                    ->map(fn ($item): array => [
                        $item->description,
                        $this->formatNumber($item->quantity, decimals: 2, trimZeros: true),
                        // Model accessor — returns unit_other when the unit is «سایر».
                        $item->unit_label,
                        $item->notes,
                    ])
                    ->all(),
            ],

            // Attachments keep their relation manager. This strip is the only
            // place «پیوست‌ها» is printed — the relation manager's own table
            // heading, which repeated it, is cleared in the manager itself.
            'panel' => [
                'heading' => __('inquiries.section.attachments'),
                'blocks' => [
                    ['type' => 'relations'],
                ],
            ],

            'cards' => [
                [
                    'heading' => __('inquiries.field.description'),
                    'rows' => [
                        // No row label: the card's header strip above already
                        // reads «توضیحات», so labelling the single row with the
                        // same string printed it twice.
                        ['value' => $inquiry->description, 'long' => true],
                    ],
                ],
            ],
        ];
    }
}
