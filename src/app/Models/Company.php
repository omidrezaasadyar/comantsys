<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class Company extends Model
{
    protected $fillable = [
        'name',
        'name_en',
        'national_id',
        'economic_code',
        'registration_no',
        'address',
        'address_en',
        'postal_code',
        'phone',
        'mobile',
        'messenger_phone',
        'email',
        'website',
        'logo_path',
        'stamp_path',
        'locale',
        'default_currency',
        'footer_note',
        'prefix',
        'seq_padding',
        'seq_start',
        'invoice_counter',
        'invoice_period',
        'proforma_counter',
        'proforma_period',
        'counter_reset',
        'verify_url_base',
    ];
    protected static function booted(): void
    {
        static::deleting(function (Company $company) {
            // شرکتی که فاکتور دارد قابل حذف نیست
            if ($company->invoices()->exists()) {
                throw new \App\Exceptions\CompanyHasInvoicesException(
                    'این شرکت دارای فاکتور است و قابل حذف نیست. ابتدا فاکتورهای آن را حذف کنید.'
                );
            }
        });
    }
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * شمارهٔ سال و ماه را بر اساس locale برمی‌گرداند:
     * fa → شمسی (سال ۳ رقمی، ماه)، en → میلادی.
     * خروجی: ['year' => '405', 'month' => '2']
     */
    public function periodParts(\DateTimeInterface $date): array
    {
        if ($this->locale === 'fa') {
            $j = Jalalian::fromDateTime($date);
            return [
                'year'  => substr((string) $j->getYear(), -3), // 1405 → 405
                'month' => (string) $j->getMonth(),
            ];
        }

        return [
            'year'  => substr($date->format('Y'), -2),         // 2026 → 26
            'month' => (string) ((int) $date->format('n')),
        ];
    }

    /**
     * تولید شمارهٔ فاکتور/پیش‌فاکتور به‌صورت اتمیک.
     * فرمت: {PREFIX}[-P]-{MM}-{YY}-{SEQ}
     * شمارنده بر اساس counter_reset (monthly|yearly|never) ریست می‌شود.
     */
    public function generateNumber(string $type, \DateTimeInterface $date): string
    {
        $isProforma = $type === 'proforma';

        $counterField = $isProforma ? 'proforma_counter' : 'invoice_counter';
        $periodField  = $isProforma ? 'proforma_period'  : 'invoice_period';

        $parts = $this->periodParts($date);

        // برچسب دوره برای تشخیص ریست
        $period = match ($this->counter_reset) {
            'monthly' => $parts['year'] . '-' . $parts['month'],
            'yearly'  => $parts['year'],
            default   => 'static', // never
        };

        // افزایش اتمیک شمارنده داخل تراکنش (جلوگیری از شمارهٔ تکراری در همزمانی)
        $seq = DB::transaction(function () use ($counterField, $periodField, $period) {
            // قفل رکورد شرکت تا پایان تراکنش
            $company = self::query()->lockForUpdate()->find($this->id);

            if ($company->{$periodField} === $period) {
                $next = $company->{$counterField} + 1;
            } else {
                $next = $this->seq_start; // دورهٔ جدید → شروع از seq_start
            }

            $company->{$counterField} = $next;
            $company->{$periodField}  = $period;
            $company->saveQuietly();

            return $next;
        });

        $seqStr = str_pad((string) $seq, $this->seq_padding, '0', STR_PAD_LEFT);

        $segments = [$this->prefix];
        if ($isProforma) {
            $segments[] = 'P';
        }
        $segments[] = $parts['month'];
        $segments[] = $parts['year'];
        $segments[] = $seqStr;

        return implode('-', $segments);
    }
}
