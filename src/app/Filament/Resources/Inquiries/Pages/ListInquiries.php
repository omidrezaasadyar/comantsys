<?php

namespace App\Filament\Resources\Inquiries\Pages;

use App\Filament\Resources\Inquiries\InquiryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

class ListInquiries extends ListRecords
{
    protected static string $resource = InquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Same mechanism as the Sale and Customer lists: Filament's page wrapper
     * renders `{{ $header }}` INSTEAD of its default header once getHeader()
     * returns a view — the heading, breadcrumbs and header actions in the
     * `@else` branch are skipped entirely
     * (filament/resources/views/components/page/index.blade.php:45-77). So the
     * default «استعلام‌ها» heading is not printed twice, provided the band
     * renders the actions itself: getCachedHeaderActions() hands over the
     * already-booted CreateAction above, keeping its «ایجاد استعلام» label,
     * icon and authorization.
     *
     * The icon matches the one ViewInquiry puts on its own band, so the list
     * and the record page read as the same module.
     */
    public function getHeader(): ?View
    {
        return view('filament.records.partials.header-band', [
            'header' => [
                'icon' => Heroicon::OutlinedDocumentMagnifyingGlass,
                'title' => __('inquiries.plural'),
                'subtitle' => __('inquiries.list_subtitle'),
                'breadcrumbs' => [
                    ['label' => __('inquiries.plural'), 'url' => null],
                ],
                'actions' => $this->getCachedHeaderActions(),
            ],
        ]);
    }
}
