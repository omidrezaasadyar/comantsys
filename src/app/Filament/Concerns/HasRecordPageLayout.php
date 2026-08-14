<?php

namespace App\Filament\Concerns;

use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

/**
 * Custom full-page record layout: header band → stat cards → two-column body
 * (relation-manager side panel + detail cards).
 *
 * How it hangs together, per Filament v5 source:
 *
 *  - `content()` is ViewRecord's content slot. We replace its whole component
 *    list with a single `Schemas\Components\View`, so nothing renders except
 *    our blade.
 *  - Relation managers are NOT dropped. `getRelationManagersContentComponent()`
 *    (Resources/Pages/Concerns/HasRelationManagers.php:109) returns a real
 *    `Schemas\Components\Tabs` component. We attach it as the View component's
 *    *child schema*, and the blade prints it with `{{ $getChildSchema() }}` —
 *    the same mechanism Filament's own grid.blade.php uses. That relocates the
 *    tabs into our column with all Livewire wiring intact.
 *  - The default page header is suppressed by making heading, subheading,
 *    breadcrumbs and header actions all empty; pages/page.blade.php only emits
 *    `<x-filament-panels::header>` when at least one of them is filled.
 *
 * A page supplies nothing but its own field mapping via getRecordPageSchema().
 */
trait HasRecordPageLayout
{
    use RecordValueHelpers;

    /**
     * Stat-card halo hues, keyed by data meaning rather than by resource, so a
     * given kind of fact looks the same on every page.
     *
     * @var array<string>
     */
    protected const STAT_GLOWS = ['sky', 'green', 'violet', 'amber'];

    /**
     * Colours a card row's badge may use — exactly the set the panel registers
     * (FilamentColor::getColors() → danger, gray, info, primary, success,
     * warning). Whitelisted so a colour can only ever reach the blade as a
     * name `<x-filament::badge>` understands.
     *
     * @var array<string>
     */
    protected const BADGE_COLORS = ['danger', 'gray', 'info', 'primary', 'success', 'warning'];

    /** Rendered wherever a value is blank. */
    protected string $recordPagePlaceholder = '—';

    /**
     * Per-page field mapping. Shape:
     *
     * [
     *     'header' => [
     *         'icon'        => Heroicon::…,          // fallback when no image
     *         'image'       => 'https://…',          // private-disk signed URL
     *         'title'       => '…',
     *         'badge'       => ['label' => '…', 'color' => 'success'],
     *         'subtitle'    => '…',
     *         'breadcrumbs' => [['label' => '…', 'url' => '…' | null]],
     *         'edit_url'    => '…' | null,           // null hides the button
     *     ],
     *     'stats' => [[
     *         'icon' => Heroicon::…, 'label' => '…', 'value' => '…',
     *         'ltr' => bool, 'url' => …,
     *         // Halo hue, picked by MEANING so it reads the same on every page:
     *         //   sky = identity/codes · green = money · violet = location/
     *         //   currency/dates · amber = contact/website/attachments.
     *         'glow' => 'sky' | 'green' | 'violet' | 'amber' | null,
     *     ]],
     *     // Optional full-width line-items table, rendered under the stat cards.
     *     'items' => [
     *         'heading' => '…',
     *         'columns' => [['label' => '…', 'align' => 'start|center|end', 'ltr' => bool]],
     *         'rows'    => [['…', '…']],        // scalars, or per-cell arrays
     *         'totals'  => [['label' => '…', 'value' => '…']],
     *         'empty'   => '…',
     *     ],
     *     // Opt-in body layout. When present it REPLACES 'items' + 'panel' +
     *     // 'cards': each row is a list of blocks laid out as grid columns,
     *     // all stretched to the same height. The first block is the
     *     // RIGHTMOST column under RTL. `width` is an fr token, e.g. '1.8fr'.
     *     'rows' => [
     *         [ <block> + ['width' => '1fr'], <block> + ['width' => '1.8fr'] ],
     *     ],
     *     'panel' => [
     *         'heading' => '…' | null,
     *         'grow_last' => bool,   // last block absorbs the column's slack

     *         // Defaults to a single 'relations' block, i.e. the resource's
     *         // relation-manager tabs.
     *         'blocks' => [
     *             ['type' => 'relations'],
     *             ['type' => 'card',  'heading' => '…', 'rows' => [ …detail rows… ]],
     *             ['type' => 'table', 'heading' => '…', 'columns' => […], 'rows' => […], 'empty' => '…'],
     *             ['type' => 'image', 'heading' => '…', 'url' => …, 'alt' => '…',
     *              'icon' => Heroicon::…, 'empty' => '…'],   // centred image + placeholder
     *         ],
     *     ],
     *     // A card with a blank 'heading' renders with no header strip — used
     *     // by single-field cards whose row carries the name inline.
     *     // 'columns' => 2 lays the rows out as a two-column label/value grid.
     *     'cards' => [['heading' => '…', 'columns' => 1 | 2, 'rows' => [
     *         ['label' => '…', 'value' => '…', 'ltr' => bool, 'long' => bool,
     *          'inline' => bool, 'url' => …,
     *          // Renders the value as a coloured pill; one of BADGE_COLORS.
     *          'badge' => 'success' | 'warning' | 'danger' | 'info' | …],
     *     ]]],
     * ]
     *
     * @return array<string, mixed>
     */
    abstract protected function getRecordPageSchema(): array;

    /**
     * @return view-string
     */
    protected function getRecordPageBlade(): string
    {
        return 'filament.records.page';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make($this->getRecordPageBlade())
                ->viewData(fn (): array => $this->normaliseRecordPage($this->getRecordPageSchema()))
                // The relation-manager Tabs component rides along as the child
                // schema so the blade can place it inside the side panel.
                ->schema(array_values(array_filter([$this->getRecordPageRelationsComponent()]))),
        ]);
    }

    /**
     * The relation-manager Tabs component, or null on a page that has none.
     *
     * `getRelationManagersContentComponent()` lives on
     * Resources/Pages/Concerns/HasRelationManagers, i.e. only on resource
     * pages. Everything else in this trait works just as well on a standalone
     * `Filament\Pages\Page` — it has the same `content()` slot, and its default
     * view (filament-panels::pages.page) renders `{{ $this->content }}` — so
     * that one call is the only thing that would couple the layout to a
     * resource. Routing it through here lets an off-resource page reuse the
     * layout; it just supplies a schema with no relations block.
     *
     * Resource pages are unaffected: the method exists there, so they still get
     * exactly the component they got before.
     */
    protected function getRecordPageRelationsComponent(): ?Component
    {
        return method_exists($this, 'getRelationManagersContentComponent')
            ? $this->getRelationManagersContentComponent()
            : null;
    }

    // ── Default page header suppression ────────────────────────────────────
    // Our band already shows the title, breadcrumbs and Edit button.

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

    /**
     * Re-labels the relation-manager tabs without touching the RelationManager
     * classes. Safe because tabs.blade.php matches the active tab on the array
     * key (`getComponents(withOriginalKeys: true)`), never on the label.
     */
    protected function relabelRelationManagerTabs(Component $component, array $labels): Component
    {
        if ($labels === [] || ! $component instanceof Tabs) {
            return $component;
        }

        $managers = $this->getRelationManagers();
        $tabs = $component->getDefaultChildComponents();

        if (! is_array($tabs)) {
            return $component;
        }

        foreach ($tabs as $key => $tab) {
            $manager = $managers[$key] ?? null;

            if ($manager === null) {
                continue;
            }

            $label = $labels[$this->normalizeRelationManagerClass($manager)] ?? null;

            if (filled($label)) {
                $tab->label($label);
            }
        }

        return $component->tabs($tabs);
    }

    // ── Normalisation (keeps the blades dumb) ──────────────────────────────

    /**
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    protected function normaliseRecordPage(array $page): array
    {
        return [
            'header' => $this->normaliseRecordPageHeader($page['header'] ?? []),
            'stats' => $this->normaliseRecordPageStats($page['stats'] ?? []),
            'items' => filled($page['items'] ?? null)
                ? $this->normaliseRecordPageTable($page['items'])
                : null,
            // When a page supplies 'rows', the blade uses them instead of the
            // items-table + side-panel body.
            'rows' => $this->normaliseRecordPageRows($page['rows'] ?? []),
            'panel' => [
                'heading' => $page['panel']['heading'] ?? null,
                // Opt-in: let the LAST block in the panel column absorb the
                // slack, so the panel column and the detail column next to it
                // end on the same line. Off by default — a page whose panel is
                // a single relations block already stretches (.cs-rp-panel
                // carries flex: 1 1 auto) and does not need it.
                'grow_last' => (bool) ($page['panel']['grow_last'] ?? false),
                'blocks' => $this->normaliseRecordPagePanelBlocks(
                    // A page that says nothing about the panel gets the
                    // resource's relation managers, which is what Supplier and
                    // every relation-heavy page wants.
                    $page['panel']['blocks'] ?? [['type' => 'relations']],
                ),
            ],
            'cards' => $this->normaliseRecordPageCards($page['cards'] ?? []),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array<string, mixed>>
     */
    protected function normaliseRecordPagePanelBlocks(array $blocks): array
    {
        $normalised = [];

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            $type = $block['type'] ?? 'relations';

            $normalised[] = $this->normaliseRecordPageBlock($block, $type);
        }

        return $normalised;
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<string, mixed>
     */
    protected function normaliseRecordPageBlock(array $block, ?string $type = null): array
    {
        $type ??= $block['type'] ?? 'relations';

        return match ($type) {
            'card' => [
                'type' => 'card',
                'heading' => $block['heading'] ?? null,
                'card' => $this->normaliseRecordPageCards([$block])[0],
            ],
            'table' => [
                'type' => 'table',
                'heading' => $block['heading'] ?? null,
                'table' => $this->normaliseRecordPageTable($block, keepEmpty: true),
            ],
            // A single centred image (company logo / stamp), with an icon
            // placeholder for when the file is absent. `url` is expected to be
            // already resolved — for a private-disk file that means
            // privateFileUrl(), which returns null when the file is missing, so
            // a stale DB path falls through to the placeholder rather than
            // rendering a broken <img>.
            'image' => [
                'type' => 'image',
                'heading' => $block['heading'] ?? null,
                'image' => [
                    'url' => $block['url'] ?? null,
                    'alt' => $block['alt'] ?? '',
                    'icon' => $block['icon'] ?? null,
                    'empty' => $block['empty'] ?? null,
                ],
            ],
            default => [
                'type' => 'relations',
                'heading' => $block['heading'] ?? null,
                'flush' => (bool) ($block['flush'] ?? false),
            ],
        };
    }

    /**
     * Opt-in body layout: explicit grid rows, used instead of the items-table
     * + side-panel default when a page's content lays out better side by side.
     *
     * A row is a plain list of blocks; each block may carry a `width` in `fr`
     * units. Under RTL the first block is the RIGHTMOST column.
     *
     * @param  array<int, array<int, array<string, mixed>>>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function normaliseRecordPageRows(array $rows): array
    {
        $normalised = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $blocks = [];
            $widths = [];

            foreach ($row as $block) {
                if (! is_array($block)) {
                    continue;
                }

                $blocks[] = $this->normaliseRecordPageBlock($block);

                $width = $block['width'] ?? null;

                // Widths reach the browser as an inline custom property, so
                // only a bare `<number>fr` token is ever let through.
                $widths[] = (is_string($width) && preg_match('/^\d+(?:\.\d+)?fr$/', $width) === 1)
                    ? $width
                    : '1fr';
            }

            if ($blocks === []) {
                continue;
            }

            $normalised[] = [
                'blocks' => $blocks,
                'template' => implode(' ', $widths),
            ];
        }

        return $normalised;
    }

    /**
     * Table alignments end up inside an inline `style`, so only the known
     * keywords are let through. `left`/`right` are physical on purpose: a
     * header cell in the RTL document and an LTR-isolated value cell resolve
     * the logical `end` to OPPOSITE edges, which is what pulls figures out of
     * line under their heading.
     */
    protected function normaliseRecordPageAlign(?string $align): string
    {
        return in_array($align, ['start', 'center', 'end', 'left', 'right'], true)
            ? $align
            : 'start';
    }

    /**
     * Widths end up inside an inline `style` too, so only a bare
     * `<number><unit>` token is accepted.
     */
    protected function normaliseRecordPageWidth(mixed $width): ?string
    {
        return (is_string($width) && preg_match('/^\d+(?:\.\d+)?(?:rem|em|px|ch|%)$/', $width) === 1)
            ? $width
            : null;
    }

    /**
     * Shared normaliser for the full-width items table and for panel tables.
     *
     * @param  array<string, mixed>  $table
     * @return array<string, mixed> | null
     */
    protected function normaliseRecordPageTable(array $table, bool $keepEmpty = false): ?array
    {
        $columns = [];

        foreach ($table['columns'] ?? [] as $column) {
            $columns[] = [
                'label' => $column['label'] ?? '',
                'align' => $this->normaliseRecordPageAlign($column['align'] ?? null),
                // Optional fixed track width for this column, so a header and
                // the figures under it share one edge and never wrap.
                'width' => $this->normaliseRecordPageWidth($column['width'] ?? null),
                'ltr' => (bool) ($column['ltr'] ?? false),
            ];
        }

        // Optional fixed width for the LAST column (the amount column). It
        // reaches the browser as an inline custom property, so only a bare
        // <number><unit> token is let through.
        $amountWidth = $table['amount_width'] ?? null;
        $amountWidth = (is_string($amountWidth) && preg_match('/^\d+(?:\.\d+)?(?:rem|em|px|ch)$/', $amountWidth) === 1)
            ? $amountWidth
            : null;

        if (filled($amountWidth) && $columns !== []) {
            // `end` resolves to opposite edges in an RTL header cell and an
            // LTR-isolated value cell, which is what pulls the amounts out of
            // line. Pin both to the same physical edge instead.
            $columns[array_key_last($columns)]['align'] = 'left';
        }

        $rows = [];

        foreach ($table['rows'] ?? [] as $row) {
            $cells = [];

            foreach (array_values((array) $row) as $index => $cell) {
                // Cells may be plain scalars; per-cell arrays override the
                // column defaults for alignment / LTR isolation.
                $cell = is_array($cell) ? $cell : ['value' => $cell];
                $value = $cell['value'] ?? null;

                $cells[] = [
                    'value' => filled($value) ? $value : $this->recordPagePlaceholder,
                    'align' => $this->normaliseRecordPageAlign(
                        $cell['align'] ?? ($columns[$index]['align'] ?? null),
                    ),
                    'width' => $this->normaliseRecordPageWidth(
                        $cell['width'] ?? ($columns[$index]['width'] ?? null),
                    ),
                    'ltr' => (bool) ($cell['ltr'] ?? ($columns[$index]['ltr'] ?? false)),
                ];
            }

            $rows[] = $cells;
        }

        $totals = [];

        foreach ($table['totals'] ?? [] as $total) {
            $value = $total['value'] ?? null;

            $totals[] = [
                'label' => $total['label'] ?? '',
                'value' => filled($value) ? $value : $this->recordPagePlaceholder,
                'ltr' => (bool) ($total['ltr'] ?? true),
                // Whitelisted so a tone can only reach the blade as a known
                // class suffix.
                'tone' => ($total['tone'] ?? null) === 'danger' ? 'danger' : null,
            ];
        }

        // A bare header row with no body reads as a broken table. Panel blocks
        // keep their shell and show an empty-state line instead; the page-level
        // items region is dropped entirely.
        if ($rows === [] && $totals === [] && ! $keepEmpty) {
            return null;
        }

        return [
            'heading' => $table['heading'] ?? null,
            'columns' => $columns,
            'rows' => $rows,
            'totals' => $totals,
            'empty' => $table['empty'] ?? '—',
            'amount_width' => $amountWidth,
        ];
    }

    /**
     * @param  array<string, mixed>  $header
     * @return array<string, mixed>
     */
    protected function normaliseRecordPageHeader(array $header): array
    {
        $badge = $header['badge'] ?? null;

        $breadcrumbs = [];

        foreach ($header['breadcrumbs'] ?? [] as $crumb) {
            if (blank($crumb['label'] ?? null)) {
                continue;
            }

            $breadcrumbs[] = [
                'label' => $crumb['label'],
                'url' => $crumb['url'] ?? null,
            ];
        }

        return [
            'icon' => $header['icon'] ?? null,
            'image' => $header['image'] ?? null,
            'title' => $header['title'] ?? '',
            'badge' => filled($badge)
                ? ['label' => $badge['label'] ?? '', 'color' => $badge['color'] ?? 'gray']
                : null,
            'subtitle' => $header['subtitle'] ?? null,
            'breadcrumbs' => $breadcrumbs,
            'edit_url' => $header['edit_url'] ?? null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $stats
     * @return array<int, array<string, mixed>>
     */
    protected function normaliseRecordPageStats(array $stats): array
    {
        $normalised = [];

        foreach ($stats as $stat) {
            $value = $stat['value'] ?? null;
            $glow = $stat['glow'] ?? null;

            $normalised[] = [
                'icon' => $stat['icon'] ?? null,
                'label' => $stat['label'] ?? '',
                'value' => filled($value) ? $value : $this->recordPagePlaceholder,
                'url' => filled($value) ? ($stat['url'] ?? null) : null,
                'ltr' => (bool) ($stat['ltr'] ?? false),
                // Whitelisted so the value can only ever reach the blade as a
                // known class suffix — never as free-form style input.
                'glow' => in_array($glow, self::STAT_GLOWS, true) ? $glow : null,
            ];
        }

        return $normalised;
    }

    /**
     * @param  array<int, array<string, mixed>>  $cards
     * @return array<int, array<string, mixed>>
     */
    protected function normaliseRecordPageCards(array $cards): array
    {
        $normalised = [];

        foreach ($cards as $card) {
            $rows = [];

            foreach ($card['rows'] ?? [] as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $value = $row['value'] ?? null;
                $badge = $row['badge'] ?? null;

                $rows[] = [
                    'label' => $row['label'] ?? '',
                    'value' => filled($value) ? $value : $this->recordPagePlaceholder,
                    // Render the value as a coloured badge. This is the custom
                    // layout's equivalent of the infolist's
                    // `TextEntry->badge()->formatStateUsing()->color()` — the
                    // caller passes the already-formatted Persian label as
                    // `value` and the colour here, both from the model's own
                    // maps. Suppressed on a blank value so the «—» placeholder
                    // never renders as an empty pill.
                    'badge' => (filled($value) && in_array($badge, self::BADGE_COLORS, true))
                        ? $badge
                        : null,
                    // A link on a placeholder would be a dead "—" anchor.
                    'url' => filled($value) ? ($row['url'] ?? null) : null,
                    'ltr' => (bool) ($row['ltr'] ?? false),
                    'long' => (bool) ($row['long'] ?? false),
                    // Label and value on ONE line, the value taking the slack
                    // (label flex:0 / value flex:1) instead of being pushed to
                    // the far edge. For a card that holds a single field and
                    // wants to stay a compact strip — the opposite of 'long',
                    // which stacks the label above a full-width value.
                    'inline' => (bool) ($row['inline'] ?? false),
                ];
            }

            // Rows per line inside the card. 2 lays the label/value pairs out
            // as a two-column grid, for a short field list in a wide column
            // that would otherwise leave the card mostly empty. Whitelisted so
            // it can only ever reach the blade as a known class suffix.
            $columns = (int) ($card['columns'] ?? 1);

            $normalised[] = [
                'heading' => $card['heading'] ?? '',
                'columns' => $columns === 2 ? 2 : 1,
                'rows' => $rows,
            ];
        }

        return $normalised;
    }
}
