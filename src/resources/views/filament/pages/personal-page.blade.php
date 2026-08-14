<x-filament-panels::page>
    <div class="cs-personal">
        {{-- tab bar --}}
        <div class="cs-tabs">
            @foreach ($this->getTabs() as $key => $label)
                <button
                    type="button"
                    wire:click="$set('activeTab', '{{ $key }}')"
                    @class([
                        'cs-tab',
                        'cs-tab--active' => $activeTab === $key,
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- active tab body --}}
        <div class="cs-tab-body">
            @if ($activeTab === 'transactions')
                @livewire(\App\Livewire\Partner\TransactionsTab::class)
            @endif
        </div>
    </div>

    <style>
        .cs-personal {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .cs-tabs {
            display: flex;
            gap: 0.5rem;
            border-bottom: 1px solid rgb(255 255 255 / 0.1);
            padding-bottom: 0.75rem;
        }
        .cs-tab {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            color: rgb(148 163 184);
            transition: all 0.15s ease;
        }
        .cs-tab:hover {
            color: rgb(226 232 240);
            background: rgb(255 255 255 / 0.05);
        }
        .cs-tab--active {
            color: rgb(96 165 250);
            background: rgb(59 130 246 / 0.12);
        }
        .cs-placeholder {
            padding: 3rem 1.5rem;
            text-align: center;
            color: rgb(148 163 184);
            border: 1px dashed rgb(255 255 255 / 0.15);
            border-radius: 0.75rem;
        }
    </style>
</x-filament-panels::page>
