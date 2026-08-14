<?php

namespace App\Filament\Resources\Suppliers\Pages;

use App\Filament\Concerns\HasRecordPageLayout;
use App\Filament\Resources\Suppliers\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\Suppliers\RelationManagers\ContactsRelationManager;
use App\Filament\Resources\Suppliers\RelationManagers\PartsRelationManager;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Models\Supplier;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Component;
use Filament\Support\Icons\Heroicon;

class ViewSupplier extends ViewRecord
{
    use HasRecordPageLayout;

    protected static string $resource = SupplierResource::class;

    /**
     * Tab order for this page only — the approved design lists contacts,
     * attachments, then parts. Overriding here rather than in
     * SupplierResource::getRelations() keeps the change scoped to the page.
     *
     * @return array<class-string>
     */
    protected function getAllRelationManagers(): array
    {
        return [
            ContactsRelationManager::class,
            AttachmentsRelationManager::class,
            PartsRelationManager::class,
        ];
    }

    /**
     * Relabels the tabs without touching the RelationManager classes, so their
     * tables, actions and empty states stay exactly as they are.
     */
    public function getRelationManagersContentComponent(): Component
    {
        /** @var Supplier $supplier */
        $supplier = $this->getRecord();

        return $this->relabelRelationManagerTabs(
            parent::getRelationManagersContentComponent(),
            [
                ContactsRelationManager::class => 'افراد تماس اصلی',
                AttachmentsRelationManager::class => sprintf('پیوست‌ها (%d)', $supplier->attachments()->count()),
                PartsRelationManager::class => 'قطعات و قیمت‌گذاری',
            ],
        );
    }

    /**
     * Supplier's mapping for the custom record page.
     *
     * @return array<string, mixed>
     */
    protected function getRecordPageSchema(): array
    {
        /** @var Supplier $supplier */
        $supplier = $this->getRecord();

        $location = $this->joinFilled([$supplier->city, $supplier->country]);
        $websiteUrl = $this->externalUrl($supplier->website);

        return [
            'header' => [
                // Supplier has no image column; the partial falls back to the
                // icon. A model with a private-disk file would instead pass
                // 'image' => $this->privateFileUrl($record->logo_path).
                'icon' => Heroicon::OutlinedBuildingStorefront,
                'title' => $supplier->name,
                'badge' => [
                    'label' => $supplier->is_active ? 'فعال' : 'غیرفعال',
                    'color' => $supplier->is_active ? 'success' : 'gray',
                ],
                'subtitle' => $this->joinFilled(['تأمین‌کننده صنایع قطعه‌سازی', $location], ' · '),
                'breadcrumbs' => [
                    ['label' => 'تأمین‌کنندگان', 'url' => SupplierResource::getUrl('index')],
                    ['label' => $supplier->name, 'url' => null],
                ],
                'edit_url' => SupplierResource::canEdit($supplier)
                    ? SupplierResource::getUrl('edit', ['record' => $supplier])
                    : null,
            ],

            'stats' => [
                [
                    'icon' => Heroicon::OutlinedGlobeAlt,
                    'label' => 'کشور مبدأ',
                    'value' => $supplier->country,
                ],
                [
                    'icon' => Heroicon::OutlinedMapPin,
                    'label' => 'شهر محل استقرار',
                    'value' => $supplier->city,
                ],
                [
                    'icon' => Heroicon::OutlinedPhone,
                    'label' => 'تلفن تماس مستقیم',
                    'value' => $supplier->phone,
                    'ltr' => true,
                ],
                [
                    'icon' => Heroicon::OutlinedGlobeAmericas,
                    'label' => 'نشانی وبسایت',
                    'value' => $supplier->website,
                    'ltr' => true,
                    'url' => $websiteUrl,
                ],
            ],

            'panel' => [
                'heading' => 'اطلاعات مرتبط',
            ],

            'cards' => [
                [
                    'heading' => 'راه‌های ارتباطی ثانویه',
                    'rows' => [
                        ['label' => 'ایمیل اصلی شرکت', 'value' => $supplier->email, 'ltr' => true],
                        [
                            'label' => 'وبسایت پشتیبان',
                            'value' => $supplier->website,
                            'ltr' => true,
                            'url' => $websiteUrl,
                        ],
                        ['label' => 'نشانی پستی دقیق', 'value' => $supplier->address, 'long' => true],
                    ],
                ],
                [
                    'heading' => 'اطلاعات تکمیلی و برچسب‌گذاری',
                    'rows' => [
                        ['label' => 'دسته‌بندی‌ها و برچسب‌ها', 'value' => $supplier->tags],
                        ['label' => 'یادداشت‌های داخلی سامانه', 'value' => $supplier->notes, 'long' => true],
                    ],
                ],
            ],
        ];
    }
}
