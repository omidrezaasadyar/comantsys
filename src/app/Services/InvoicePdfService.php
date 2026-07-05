<?php

namespace App\Services;

use App\Models\Invoice;
use App\Support\NumberToWords;
use Spatie\Browsershot\Browsershot;

class InvoicePdfService
{
    public static function generate(Invoice $invoice): string
    {
        $invoice->load(['company', 'customer', 'items']);

        $totalInWords = $invoice->locale === 'fa'
            ? NumberToWords::convert($invoice->grand_total) . ' ریال'
            : '';

        $logoData = self::imageToBase64($invoice->company?->logo_path);
        $stampData = self::imageToBase64($invoice->company?->stamp_path);
        $barcodeData = self::generateBarcode($invoice->invoice_number);
        $qrData = self::generateQr($invoice);
        $fonts = self::fontFaces();

        $template = $invoice->template ?: 'magan_fa';
        $view = 'invoices.pdf.' . $template;

        $html = view($view, [
            'invoice'      => $invoice,
            'totalInWords' => $totalInWords,
            'logoPath'     => $logoData,
            'stampPath'    => $stampData,
            'barcodeData'  => $barcodeData,
            'qrData'       => $qrData,
            'fontFaces'    => $fonts,
        ])->render();

        return Browsershot::html($html)
            ->setNodeBinary('/usr/bin/node')
            ->setNpmBinary('/usr/bin/npm')
            ->setChromePath('/usr/bin/chromium')
            ->noSandbox()
            ->format('A4')
            ->margins(5, 5, 5, 5)
            ->showBackground()
            ->pdf();
    }

    protected static function fontFaces(): string
    {
        $dir = storage_path('fonts/vazirmatn');
        $weights = [
           400 => 'Vazirmatn-Regular.ttf',
           500 => 'Vazirmatn-Medium.ttf',
           700 => 'Vazirmatn-Bold.ttf',
           800 => 'Vazirmatn-ExtraBold.ttf',
];

        $css = '';
        foreach ($weights as $weight => $file) {
            $path = $dir . '/' . $file;
            if (file_exists($path)) {
                $b64 = base64_encode(file_get_contents($path));
                $css .= "@font-face { font-family: 'Vazirmatn'; font-weight: {$weight}; "
                    . "src: url(data:font/truetype;base64,{$b64}) format('truetype'); }\n";
            }
        }

        return $css;
    }

    protected static function imageToBase64(?string $relativePath): ?string
    {
        if (empty($relativePath)) {
            return null;
        }

        $path = storage_path('app/private/' . $relativePath);
        if (! file_exists($path)) {
            $path = storage_path('app/public/' . $relativePath);
            if (! file_exists($path)) {
                return null;
            }
        }

        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);

        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }

    protected static function generateBarcode(?string $number): ?string
    {
        if (empty($number)) {
            return null;
        }

        $generator = new \Milon\Barcode\DNS1D();

        $png = $generator->getBarcodePNG($number, 'C128', 2, 50);

        return 'data:image/png;base64,' . $png;
    }

    protected static function generateQr(Invoice $invoice): ?string
    {
        $base = $invoice->company?->verify_url_base;
        if (empty($base)) {
            return null;
        }

        $url = rtrim($base, '/') . '/' . $invoice->invoice_number;

        $svg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(150)
            ->margin(1)
            ->generate($url);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
