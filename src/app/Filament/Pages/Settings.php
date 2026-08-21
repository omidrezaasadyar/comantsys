<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use App\Support\AppInfo;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

class Settings extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static ?string $navigationLabel = 'تنظیمات';
    protected static ?int $navigationSort = 99;
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.settings';

    public function getTitle(): string
    {
        return 'تنظیمات';
    }

    public function getHeader(): ?View
    {
        return view('filament.records.partials.header-band', [
            'header' => [
                'icon' => Heroicon::OutlinedCog6Tooth,
                'title' => 'تنظیمات',
                'subtitle' => 'مدیریت بخش‌های سیستمی برنامه: پشتیبان‌گیری، کاربران، دسترسی‌ها و نسخه.',
                'breadcrumbs' => [
                    ['label' => 'تنظیمات', 'url' => null],
                ],
            ],
        ]);
    }

    /**
     * Modal for editing the app version + last-updated date.
     * Filament resolves the action named 'editVersion' to this method by the
     * '<name>Action' convention; the view triggers it via mountAction().
     * Page actions work without adding a trait here — BasePage already uses
     * InteractsWithActions.
     */
    public function editVersionAction(): Action
    {
        return Action::make('editVersion')
            ->modalHeading('ویرایش نسخهٔ نرم‌افزار')
            ->modalDescription('شمارهٔ نسخه و تاریخ آخرین به‌روزرسانی که در صفحهٔ ورود و نوار کناری نمایش داده می‌شود.')
            ->modalSubmitActionLabel('ذخیره')
            ->modalWidth(Width::Medium)
            ->fillForm(fn (): array => [
                'app_version'    => AppInfo::version(),
                'app_updated_at' => AppInfo::updatedAtRaw(),
            ])
            ->schema([
                TextInput::make('app_version')
                    ->label('شمارهٔ نسخه')
                    ->required()
                    ->maxLength(50)
                    ->placeholder('v2.5'),
                DatePicker::make('app_updated_at')
                    ->label('تاریخ آخرین به‌روزرسانی')
                    ->jalali()
                    ->required(),
            ])
            ->action(function (array $data): void {
                AppSetting::set('app_version', $data['app_version']);
                // DatePicker returns a Y-m-d Gregorian string; store as-is.
                AppSetting::set('app_updated_at', $data['app_updated_at']);

                \Filament\Notifications\Notification::make()
                    ->title('نسخه به‌روزرسانی شد')
                    ->success()
                    ->send();
            });
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }
}
