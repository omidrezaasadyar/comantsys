<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasRecordPageLayout;
use App\Filament\Resources\PartnerTransactions\Schemas\PartnerTransactionInfolist;
use App\Models\PartnerTransaction;
use Filament\Pages\Page;
use Filament\Panel;
use Livewire\Attributes\Locked;

/**
 * Read-only detail view of a single partner transaction, reached from the
 * «صفحه شخصی» transactions tab.
 *
 * Renders through HasRecordPageLayout — the same trait, the same
 * filament.records.page blade and the same PartnerTransactionInfolist mapping
 * the admin ViewPartnerTransaction uses, so the two pages cannot drift apart.
 * A standalone Page rather than a second resource page, because the record
 * resolution here has to be owner-scoped and a resource page resolves its
 * record from the resource's own query.
 */
class PartnerTransactionView extends Page
{
    use HasRecordPageLayout;

    /**
     * Reached only by clicking a row in the personal page's tab.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'partner-transaction';

    /**
     * `#[Locked]` for the same reason Filament's own InteractsWithRecord locks
     * it: without it, the record is a plain public Livewire property and a
     * later request could ask to be rehydrated against a different row. Locked,
     * Livewire rejects any client-side change, so the only thing that ever
     * assigns it is the owner-scoped query in mount().
     */
    #[Locked]
    public PartnerTransaction $record;

    /**
     * The slug alone would give a parameter-less route, and putting `{record}`
     * in $slug would drag it into the route NAME too (getRelativeRouteName()
     * just swaps `/` for `.`). Overriding the path keeps the name clean:
     * filament.admin.pages.partner-transaction.
     */
    public static function getRoutePath(Panel $panel): string
    {
        return '/partner-transaction/{record}';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['partner', 'super_admin']) ?? false;
    }

    /**
     * SECURITY INVARIANT — a partner resolves only their OWN row.
     *
     * The guard is here, in record resolution, not on the visibility of the
     * link that leads here: hand-typing another id, or editing the URL, hits
     * this same query and gets a 404 from findOrFail(), because the row is
     * simply not in the scoped result set. There is no code path into this page
     * that skips it.
     */
    public function mount(int | string | PartnerTransaction $record): void
    {
        // PartnerTransaction MUST stay in this union. Route-model binding hands
        // this page a model, and a scalar-only `int|string` hint does not
        // reject it — the file is in weak typing mode, so PHP coerces the
        // object via Model::__toString(), i.e. toJson(). The body would then
        // receive the record's entire JSON as its "key" and the lookup would
        // hit Postgres as `where id = '{"id":7,...}'` → SQLSTATE[22P02].
        // Naming the model in the union makes it an exact match, so it arrives
        // intact and is reduced to a key here instead.
        $key = $record instanceof PartnerTransaction
            ? $record->getKey()
            : $record;

        $query = PartnerTransaction::query()->with(['user', 'creator']);

        // Everyone except a super_admin is confined to their own rows. The
        // scoped branch is the default; the bypass is the explicit exception,
        // so a failure to evaluate the role check can only ever narrow access.
        if (! (auth()->user()?->hasRole('super_admin') ?? false)) {
            $query->where('user_id', auth()->id());
        }

        $this->record = $query->findOrFail($key);
    }

    public function getTitle(): string
    {
        return 'جزئیات تراکنش';
    }

    /**
     * Same mapping the admin view renders, so a field added there shows up here
     * with no second edit.
     *
     * @return array<string, mixed>
     */
    protected function getRecordPageSchema(): array
    {
        $schema = PartnerTransactionInfolist::schema($this->record);

        // Navigation chrome only — every field, label and format above stays
        // shared with the admin view. The builder's breadcrumb points at the
        // resource index and its Edit button at the resource edit page, neither
        // of which a partner can open; this page is read-only and belongs to
        // the personal page.
        $schema['header']['breadcrumbs'] = [
            ['label' => 'صفحه شخصی', 'url' => PersonalPage::getUrl()],
            ['label' => 'جزئیات تراکنش', 'url' => null],
        ];

        $schema['header']['edit_url'] = null;

        return $schema;
    }
}
