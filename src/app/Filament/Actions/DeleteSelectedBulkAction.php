<?php

namespace App\Filament\Actions;

use Filament\Actions\DeleteBulkAction;

/**
 * Shared bulk-delete action for every list table.
 *
 * Extends Filament's built-in DeleteBulkAction — the delete loop, the
 * confirmation modal, the deselect-after-completion behaviour and the
 * partial-failure notifications all come from the parent unchanged.
 * Only the Persian wording and the authorization depth are customised.
 *
 * Authorization (Filament v5.6, Resources/Pages/Page.php:308 & 318):
 *  - Visibility  → `deleteAny` policy method (`DeleteAny:X` Shield permission).
 *  - Per record  → `authorizeIndividualRecords()` is OFF by default in v5
 *    (CanBeAuthorized::shouldAuthorizeIndividualRecords() → filled(null) === false),
 *    so without the call below the per-row `delete` policy is never consulted.
 *    Enabling it makes the page's default resolver run
 *    `Resource::getDeleteAuthorizationResponse($record)` on every selected row;
 *    rows the user may not delete are skipped and reported, not deleted.
 *
 * Deletes are NOT wrapped in a transaction (CanUseDatabaseTransactions
 * defaults to false), and `fetchSelectedRecords` defaults to true, so each
 * row goes through `$record->delete()` — model `deleting` guards do fire.
 */
class DeleteSelectedBulkAction extends DeleteBulkAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('حذف');

        $this->requiresConfirmation();

        $this->modalHeading('حذف موارد انتخاب‌شده');
        $this->modalDescription('این عملیات قابل بازگشت نیست. آیا از حذف موارد انتخاب‌شده مطمئن هستید؟');
        $this->modalSubmitActionLabel('حذف');
        $this->modalCancelActionLabel('انصراف');

        $this->authorizeIndividualRecords();
    }
}
