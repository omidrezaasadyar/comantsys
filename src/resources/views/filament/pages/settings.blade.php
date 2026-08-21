{{-- Settings cards. Icons use <x-filament::icon> with the Heroicon enum — the
     same convention as records/partials/header-band.blade.php. Tabler ("ti ti-*")
     is NOT installed in this project and would render as empty boxes. --}}
@php
    use Filament\Support\Icons\Heroicon;
@endphp

<x-filament-panels::page>
    <div class="cs-settings-grid">

        <div class="cs-set-card cs-set-card-static">
            <div class="cs-set-card-top">
                <span class="cs-set-icon"><x-filament::icon :icon="\Filament\Support\Icons\Heroicon::OutlinedCircleStack" /></span>
            </div>
            <div class="cs-set-title">پشتیبان‌گیری دیتابیس</div>
            <div class="cs-set-desc">تهیهٔ نسخهٔ پشتیبان و بازگردانی. همین‌جا اجرا می‌شود.</div>
            <div class="cs-set-backup-actions">
                @livewire(\App\Filament\Widgets\DatabaseBackupWidget::class)
            </div>
        </div>

        <a href="{{ route('filament.admin.resources.users.index') }}" class="cs-set-card">
            <div class="cs-set-card-top">
                <span class="cs-set-icon"><x-filament::icon :icon="Heroicon::OutlinedUserGroup" /></span>
                <x-filament::icon :icon="Heroicon::OutlinedArrowLeft" class="cs-set-arrow" />
            </div>
            <div class="cs-set-title">مدیریت کاربران</div>
            <div class="cs-set-desc">افزودن و ویرایش کاربران و تخصیص نقش‌ها.</div>
        </a>

        <a href="{{ route('filament.admin.resources.shield.roles.index') }}" class="cs-set-card">
            <div class="cs-set-card-top">
                <span class="cs-set-icon"><x-filament::icon :icon="Heroicon::OutlinedShieldCheck" /></span>
                <x-filament::icon :icon="Heroicon::OutlinedArrowLeft" class="cs-set-arrow" />
            </div>
            <div class="cs-set-title">نقش‌ها و دسترسی‌ها</div>
            <div class="cs-set-desc">تعریف نقش‌ها و مدیریت مجوزهای هر بخش.</div>
        </a>

        <div class="cs-set-card cs-set-card-static">
            <div class="cs-set-card-top">
                <span class="cs-set-icon"><x-filament::icon :icon="Heroicon::OutlinedRectangleStack" /></span>
                <span class="cs-set-version-chip">{{ \App\Support\AppInfo::version() }}</span>
            </div>
            <div class="cs-set-title">نسخهٔ نرم‌افزار</div>
            <div class="cs-set-desc">نسخهٔ فعلی و تاریخ به‌روزرسانی. برای تغییر، ویرایش را بزنید.</div>
            <button type="button"
                    wire:click="mountAction('editVersion')"
                    class="cs-set-edit-btn">
                <x-filament::icon :icon="Heroicon::OutlinedPencilSquare" />
                <span>ویرایش</span>
            </button>
        </div>

    </div>

    <style>
        .cs-settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            padding: 0.25rem 0;
        }
        .cs-set-card {
            display: block;
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            padding: 16px;
            text-decoration: none;
            transition: border-color 0.15s, transform 0.15s;
        }
        .cs-set-card:hover:not(.cs-set-card-static) {
            border-color: #E8590C;
            transform: translateY(-1px);
        }
        .dark .cs-set-card {
            background: #1e293b;
            border-color: rgba(255, 255, 255, 0.10);
        }
        .cs-set-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .cs-set-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #FBE6D6;
            color: #B84503;
            font-size: 20px;
        }
        /* The Filament icon component emits an SVG; size it explicitly, since an
           SVG does not scale from the parent font-size the way an icon font does.
           NOTE: never write a literal x-component tag inside this style block —
           Blade parses component tags even inside CSS comments and would compile
           an unclosed component. */
        .cs-set-icon svg {
            width: 20px;
            height: 20px;
        }
        .dark .cs-set-icon {
            background: rgba(232, 89, 12, 0.16);
            color: #F2843F;
        }
        .cs-set-arrow {
            width: 16px;
            height: 16px;
            font-size: 16px;
            color: #94a3b8;
            flex-shrink: 0;
        }
        .cs-set-title {
            font-size: 14px;
            font-weight: 500;
            color: #1e293b;
        }
        .dark .cs-set-title { color: #e2e8f0; }
        .cs-set-desc {
            font-size: 12px;
            color: #64748b;
            margin-top: 3px;
            line-height: 1.7;
        }
        .dark .cs-set-desc { color: #94a3b8; }
        .cs-set-version-chip {
            font-size: 11px;
            font-family: ui-monospace, monospace;
            color: #64748b;
            background: #f1f5f9;
            padding: 2px 10px;
            border-radius: 9999px;
        }
        .dark .cs-set-version-chip {
            color: #94a3b8;
            background: rgba(255, 255, 255, 0.06);
        }
        .cs-set-backup-actions {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }
        .dark .cs-set-backup-actions {
            border-top-color: rgba(255, 255, 255, 0.08);
        }
        .cs-set-edit-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 12px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 500;
            color: #B84503;
            background: #FBE6D6;
            border: 1px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
        }
        .cs-set-edit-btn:hover {
            background: #F8D9C2;
            border-color: #E8590C;
        }
        .cs-set-edit-btn svg {
            width: 15px;
            height: 15px;
        }
        .dark .cs-set-edit-btn {
            color: #F2843F;
            background: rgba(232, 89, 12, 0.16);
        }
        .dark .cs-set-edit-btn:hover {
            background: rgba(232, 89, 12, 0.24);
            border-color: #F2843F;
        }
    </style>
</x-filament-panels::page>
