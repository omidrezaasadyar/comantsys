<?php

namespace App\Filament\Resources\Sourcing\Pages;

use App\Filament\Concerns\HasRecordPageLayout;
use App\Filament\Resources\Sourcing\SourcingRequestResource;
use App\Models\SourcingRequest;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSourcingRequest extends ViewRecord
{
    use HasRecordPageLayout;

    protected static string $resource = SourcingRequestResource::class;

    /**
     * @return array<string, mixed>
     */
    protected function getRecordPageSchema(): array
    {
        /** @var SourcingRequest $request */
        $request = $this->getRecord();

        $status = match ($request->status) {
            'active' => __('sourcing.request_status.active'),
            'archived' => __('sourcing.request_status.archived'),
            default => null,
        };

        return [
            'header' => [
                'icon' => Heroicon::OutlinedSparkles,
                'title' => $request->part_name,
                'badge' => filled($status)
                    ? [
                        'label' => $status,
                        'color' => $request->status === 'active' ? 'success' : 'gray',
                    ]
                    : null,
                'subtitle' => $this->joinFilled([__('sourcing.model'), $request->part_number], ' · '),
                'breadcrumbs' => [
                    ['label' => __('sourcing.plural'), 'url' => SourcingRequestResource::getUrl('index')],
                    ['label' => $request->part_name, 'url' => null],
                ],
                'edit_url' => SourcingRequestResource::canEdit($request)
                    ? SourcingRequestResource::getUrl('edit', ['record' => $request])
                    : null,
            ],

            'stats' => [
                [
                    'icon' => Heroicon::OutlinedHashtag,
                    'label' => __('sourcing.field.part_number'),
                    'value' => $request->part_number,
                    'ltr' => true,
                    'glow' => 'sky',
                ],
                [
                    'icon' => Heroicon::OutlinedBolt,
                    'label' => __('sourcing.field.request_status'),
                    'value' => $status,
                    'glow' => 'violet',
                ],
                [
                    'icon' => Heroicon::OutlinedClock,
                    'label' => __('sourcing.field.runs_count'),
                    'value' => (string) $request->runs()->count(),
                    'ltr' => true,
                    'glow' => 'green',
                ],
                [
                    'icon' => Heroicon::OutlinedPaperClip,
                    'label' => __('sourcing.section.attachments'),
                    'value' => (string) $request->attachments()->count(),
                    'ltr' => true,
                    'glow' => 'amber',
                ],
            ],

            // Runs and attachments already have relation managers; the panel
            // hosts their tabs unchanged.
            'panel' => [
                'heading' => __('sourcing.section.runs'),
                'blocks' => [
                    ['type' => 'relations'],
                ],
            ],

            'cards' => [
                [
                    'heading' => __('sourcing.field.description'),
                    'rows' => [
                        ['label' => __('sourcing.field.description'), 'value' => $request->description, 'long' => true],
                    ],
                ],
                [
                    'heading' => __('sourcing.field.search_instructions'),
                    'rows' => [
                        [
                            'label' => __('sourcing.field.search_instructions'),
                            'value' => $request->search_instructions,
                            'long' => true,
                        ],
                    ],
                ],
            ],
        ];
    }
}
