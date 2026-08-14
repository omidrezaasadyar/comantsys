<?php

namespace App\Filament\Concerns;

use Ariaieboy\Jalali\Jalali;
use Carbon\Carbon;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\View;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToCheckFileExistence;
use Throwable;

/**
 * Renders a ViewRecord page through the shared "Variant C" blade
 * (summary rail + flat detail sections + optional line-items table)
 * instead of Filament's infolist card layout.
 *
 * It swaps only the *content component* that ViewRecord would otherwise fill
 * with the infolist/form schema, so the page header, header actions,
 * breadcrumbs, sub-navigation and relation managers all keep working exactly
 * as before.
 *
 * A page using this trait supplies nothing but its own field mapping via
 * getRecordViewSchema(); everything below normalises that array so the blade
 * can stay dumb.
 */
trait HasCustomRecordView
{
    /** Rendered wherever a value is blank. */
    protected string $recordViewPlaceholder = '—';

    /**
     * Per-page field mapping. Shape:
     *
     * [
     *     'rail' => [
     *         'icon'     => Heroicon::OutlinedBuildingStorefront,   // or null
     *         'image'    => 'https://…',                            // wins over icon
     *         'title'    => '…',
     *         'subtitle' => '…',                                    // optional
     *         'badge'    => ['label' => '…', 'color' => 'success'],  // optional
     *         'metric'   => ['label' => '…', 'value' => '…', 'hint' => '…', 'ltr' => true], // optional
     *         'facts'    => [['label' => '…', 'value' => '…', 'ltr' => true, 'url' => '…']],
     *     ],
     *     'sections' => [
     *         ['heading' => '…', 'rows' => [
     *             ['label' => '…', 'value' => '…', 'ltr' => false, 'long' => false, 'url' => null],
     *         ]],
     *     ],
     *     'items' => [   // optional — omit entirely when the record has no line items
     *         'heading' => '…',
     *         'columns' => [['label' => '…', 'align' => 'start|center|end', 'ltr' => false]],
     *         'rows'    => [['…', '…']],           // scalars, or per-cell arrays
     *         'totals'  => [['label' => '…', 'value' => '…']],
     *     ],
     * ]
     *
     * @return array<string, mixed>
     */
    abstract protected function getRecordViewSchema(): array;

    /**
     * @return view-string
     */
    protected function getRecordViewBlade(): string
    {
        return 'filament.records.show';
    }

    /**
     * ViewRecord::content() places this where the infolist would have gone.
     */
    public function getInfolistContentComponent(): Component
    {
        return $this->getRecordViewComponent();
    }

    /**
     * Same slot for resources that never declare an infolist.
     */
    public function getFormContentComponent(): Component
    {
        return $this->getRecordViewComponent();
    }

    protected function getRecordViewComponent(): Component
    {
        return View::make($this->getRecordViewBlade())
            ->viewData(fn (): array => $this->normaliseRecordView($this->getRecordViewSchema()));
    }

    /**
     * @param  array<string, mixed>  $view
     * @return array<string, mixed>
     */
    protected function normaliseRecordView(array $view): array
    {
        $sections = [];

        foreach ($view['sections'] ?? [] as $section) {
            $rows = array_values(array_map(
                fn (array $row): array => $this->normaliseRecordViewRow($row),
                // Callers may pass null rows to drop a conditional field.
                array_filter($section['rows'] ?? [], static fn ($row): bool => is_array($row)),
            ));

            $sections[] = [
                'heading' => $section['heading'] ?? '',
                'rows' => $rows,
                // A section holding long free text takes the full grid row,
                // rather than being squeezed into one column.
                'full' => (bool) ($section['full'] ?? false)
                    || in_array(true, array_column($rows, 'long'), true),
            ];
        }

        return [
            'rail' => $this->normaliseRecordViewRail($view['rail'] ?? []),
            'sections' => $sections,
            'items' => filled($view['items'] ?? null)
                ? $this->normaliseRecordViewItems($view['items'])
                : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $rail
     * @return array<string, mixed>
     */
    protected function normaliseRecordViewRail(array $rail): array
    {
        $badge = $rail['badge'] ?? null;
        $metric = $rail['metric'] ?? null;

        $facts = [];

        foreach ($rail['facts'] ?? [] as $fact) {
            $value = $fact['value'] ?? null;

            $facts[] = [
                'label' => $fact['label'] ?? '',
                'value' => filled($value) ? $value : $this->recordViewPlaceholder,
                'url' => filled($value) ? ($fact['url'] ?? null) : null,
                'ltr' => (bool) ($fact['ltr'] ?? false),
            ];
        }

        return [
            'icon' => $rail['icon'] ?? null,
            'image' => $rail['image'] ?? null,
            'title' => $rail['title'] ?? '',
            'subtitle' => $rail['subtitle'] ?? null,
            'badge' => filled($badge)
                ? [
                    'label' => $badge['label'] ?? '',
                    'color' => $badge['color'] ?? 'gray',
                ]
                : null,
            'metric' => filled($metric)
                ? [
                    'label' => $metric['label'] ?? '',
                    'value' => filled($metric['value'] ?? null) ? $metric['value'] : $this->recordViewPlaceholder,
                    'hint' => $metric['hint'] ?? null,
                    'ltr' => (bool) ($metric['ltr'] ?? false),
                ]
                : null,
            'facts' => $facts,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normaliseRecordViewRow(array $row): array
    {
        $value = $row['value'] ?? null;

        return [
            'label' => $row['label'] ?? '',
            'value' => filled($value) ? $value : $this->recordViewPlaceholder,
            // A link on a placeholder would be a dead "—" anchor.
            'url' => filled($value) ? ($row['url'] ?? null) : null,
            'ltr' => (bool) ($row['ltr'] ?? false),
            'long' => (bool) ($row['long'] ?? false),
        ];
    }

    /**
     * @param  array<string, mixed>  $items
     * @return array<string, mixed> | null
     */
    protected function normaliseRecordViewItems(array $items): ?array
    {
        $columns = [];

        foreach ($items['columns'] ?? [] as $column) {
            $columns[] = [
                'label' => $column['label'] ?? '',
                'align' => $column['align'] ?? 'start',
                'ltr' => (bool) ($column['ltr'] ?? false),
            ];
        }

        $rows = [];

        foreach ($items['rows'] ?? [] as $row) {
            $cells = [];

            foreach (array_values((array) $row) as $index => $cell) {
                // Cells may be plain scalars; per-cell arrays override the
                // column defaults for alignment / LTR isolation.
                $cell = is_array($cell) ? $cell : ['value' => $cell];
                $value = $cell['value'] ?? null;

                $cells[] = [
                    'value' => filled($value) ? $value : $this->recordViewPlaceholder,
                    'align' => $cell['align'] ?? ($columns[$index]['align'] ?? 'start'),
                    'ltr' => (bool) ($cell['ltr'] ?? ($columns[$index]['ltr'] ?? false)),
                ];
            }

            $rows[] = $cells;
        }

        $totals = [];

        foreach ($items['totals'] ?? [] as $total) {
            $value = $total['value'] ?? null;

            $totals[] = [
                'label' => $total['label'] ?? '',
                'value' => filled($value) ? $value : $this->recordViewPlaceholder,
                'ltr' => (bool) ($total['ltr'] ?? true),
            ];
        }

        // A bare header row with no body reads as a broken table, so an empty
        // items region is dropped entirely.
        if ($rows === [] && $totals === []) {
            return null;
        }

        return [
            'heading' => $items['heading'] ?? null,
            'columns' => $columns,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    /**
     * Gregorian in the DB, Jalali on screen — same conversion the
     * ariaieboy/filament-jalali `jalaliDate()` / `jalaliDateTime()` macros use.
     */
    protected function toJalali(mixed $value, bool $withTime = false): ?string
    {
        if (blank($value)) {
            return null;
        }

        $format = $withTime
            ? config('filament-jalali.date_time_format')
            : config('filament-jalali.date_format');

        return Jalali::fromCarbon(
            Carbon::parse($value)->setTimezone(FilamentTimezone::get()),
        )->format($format);
    }

    /**
     * Thousands-separated number, matching the infolists' existing
     * `->numeric(decimalPlaces: 0, decimalSeparator: '.', thousandsSeparator: ',')`.
     * `$trimZeros` is for quantities, where "2.00" should read as "2".
     */
    protected function formatNumber(mixed $value, int $decimals = 0, bool $trimZeros = false): ?string
    {
        if (blank($value) || ! is_numeric($value)) {
            return null;
        }

        $formatted = number_format((float) $value, $decimals, '.', ',');

        if ($trimZeros && str_contains($formatted, '.')) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }

        return $formatted;
    }

    /**
     * Signed temporary URL for a file on the private disk — the same path
     * Filament's ImageEntry takes for `->disk('local')->visibility('private')`.
     */
    protected function privateFileUrl(?string $path, string $disk = 'local'): ?string
    {
        if (blank($path)) {
            return null;
        }

        $storage = Storage::disk($disk);

        try {
            if (! $storage->exists($path)) {
                return null;
            }
        } catch (UnableToCheckFileExistence) {
            return null;
        }

        try {
            return $storage->temporaryUrl(
                $path,
                now()->addMinutes(config('filament.temporary_file_url_expiry_minutes', 30))->endOfHour(),
            );
        } catch (Throwable) {
            // Driver does not support temporary URLs — fall back to a plain one.
            return $storage->url($path);
        }
    }

    /**
     * Joins the parts that are actually present, so a missing city/country
     * does not leave a dangling separator in a subtitle.
     *
     * @param  array<int, mixed>  $parts
     */
    protected function joinFilled(array $parts, string $glue = '، '): ?string
    {
        $parts = array_values(array_filter($parts, static fn ($part): bool => filled($part)));

        return $parts === [] ? null : implode($glue, $parts);
    }
}
