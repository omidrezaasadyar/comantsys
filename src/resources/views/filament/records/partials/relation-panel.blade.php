{{--
    resources/views/filament/records/partials/relation-panel.blade.php

    Reusable side panel. Renders an ordered list of blocks so each resource can
    fill the column with whatever it actually has:

      ['type' => 'relations']  the page's relation-manager component — the very
                               same Filament\Schemas\Components\Tabs that
                               ViewRecord would have rendered full-width below
                               the page (Resources/Pages/Concerns/
                               HasRelationManagers.php:164). It arrives as the
                               host View component's child schema, so tabs,
                               tables, actions and empty states behave exactly
                               as before; only their position changed.
      ['type' => 'card']       label/value rows (meta, notes)
      ['type' => 'table']      a flat table (e.g. a hasMany the resource has no
                               relation manager for)

    Each block is drawn by partials/block.blade.php, which the two-column body
    rows use as well.

    $panel = ['heading' => … | null, 'grow_last' => bool, 'blocks' => [...]]
    $relations = the relation-manager component

    'grow_last' is opt-in: it makes the LAST block absorb the column's slack so
    this column and the detail column beside it end on the same line. A panel
    that is a single relations block does not need it — `.cs-rp-panel` already
    carries `flex: 1 1 auto` — but a panel ending in a table or card block does.
--}}

<div class="cs-rp-panel-col">
    @foreach ($panel['blocks'] as $block)
        @include('filament.records.partials.block', [
            'block' => $block,
            'relations' => $relations,
            'grow' => ($panel['grow_last'] ?? false) && $loop->last,
            // The panel-level heading labels the first block unless that block
            // brought its own.
            'heading' => $block['heading'] ?? ($loop->first ? $panel['heading'] : null),
        ])
    @endforeach
</div>
