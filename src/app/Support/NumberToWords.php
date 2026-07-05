<?php

namespace App\Support;

class NumberToWords
{
    protected static array $yekan = ['', 'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه'];

    protected static array $dahgan = ['', '', 'بیست', 'سی', 'چهل', 'پنجاه', 'شصت', 'هفتاد', 'هشتاد', 'نود'];

    protected static array $dah = ['ده', 'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده'];

    protected static array $sadgan = ['', 'صد', 'دویست', 'سیصد', 'چهارصد', 'پانصد', 'ششصد', 'هفتصد', 'هشتصد', 'نهصد'];

    protected static array $scale = ['', ' هزار', ' میلیون', ' میلیارد', ' بیلیون'];

    /**
     * تبدیل عدد به حروف فارسی.
     * مثال: 57200000000 → پنجاه و هفت میلیارد و دویست میلیون
     */
    public static function convert($number): string
    {
        $number = (int) round((float) $number);

        if ($number === 0) {
            return 'صفر';
        }

        $negative = $number < 0;
        $number = abs($number);

        // تقسیم به گروه‌های سه‌رقمی
        $groups = [];
        while ($number > 0) {
            $groups[] = $number % 1000;
            $number = intdiv($number, 1000);
        }

        $parts = [];
        for ($i = count($groups) - 1; $i >= 0; $i--) {
            if ($groups[$i] === 0) {
                continue;
            }

            $groupWords = self::threeDigitsToWords($groups[$i]);
            $parts[] = $groupWords . (self::$scale[$i] ?? '');
        }

        $result = implode(' و ', $parts);

        return $negative ? 'منفی ' . $result : $result;
    }

    /**
     * یک گروه سه‌رقمی (0 تا 999) را به حروف تبدیل می‌کند.
     */
    protected static function threeDigitsToWords(int $num): string
    {
        $words = [];

        $sad = intdiv($num, 100);
        $remainder = $num % 100;
        $dah = intdiv($remainder, 10);
        $yek = $remainder % 10;

        if ($sad > 0) {
            $words[] = self::$sadgan[$sad];
        }

        if ($remainder >= 10 && $remainder <= 19) {
            $words[] = self::$dah[$remainder - 10];
        } else {
            if ($dah > 0) {
                $words[] = self::$dahgan[$dah];
            }
            if ($yek > 0) {
                $words[] = self::$yekan[$yek];
            }
        }

        return implode(' و ', $words);
    }
    /**
     * تبدیل ارقام انگلیسی به فارسی.
     */
    public static function toPersianDigits(string $string): string
    {
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

        return str_replace($english, $persian, $string);
    }
}