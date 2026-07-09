<?php

namespace Tests\Feature\Sourcing;

use App\Models\SourcingRun;
use Tests\TestCase;

class RunResultViewTest extends TestCase
{
    private function render(SourcingRun $run): string
    {
        return view('filament.sourcing.run-result', ['record' => $run])->render();
    }

    public function test_it_renders_new_shape_with_contact_and_price(): void
    {
        $run = new SourcingRun([
            'status'  => 'completed',
            'results' => [
                'suppliers' => [
                    [
                        'name'      => 'Acme Bearings',
                        'url'       => 'https://acme.example/p/123',
                        'relevance' => 'مطابقت دقیق شماره قطعه',
                        'contact'   => [
                            'email'    => 'sales@acme.example',
                            'phone'    => '+1 555 0100',
                            'whatsapp' => null,
                        ],
                        'price' => [
                            'value'    => 12.5,
                            'currency' => 'USD',
                            'note'     => 'هر ۱۰ عدد',
                        ],
                    ],
                ],
                'summary' => 'یک تأمین‌کننده یافت شد.',
            ],
        ]);

        $html = $this->render($run);

        // Contact rendered as actionable links.
        $this->assertStringContainsString('mailto:sales@acme.example', $html);
        $this->assertStringContainsString('tel:+15550100', $html); // sanitized
        $this->assertStringContainsString('sales@acme.example', $html);
        // Price value + currency + note.
        $this->assertStringContainsString('12.5', $html);
        $this->assertStringContainsString('USD', $html);
        $this->assertStringContainsString('هر ۱۰ عدد', $html);
        // Summary + name.
        $this->assertStringContainsString('Acme Bearings', $html);
        $this->assertStringContainsString('یک تأمین‌کننده یافت شد.', $html);
    }

    public function test_it_shows_emdash_for_absent_contact_and_price(): void
    {
        $run = new SourcingRun([
            'status'  => 'completed',
            'results' => [
                'suppliers' => [
                    [
                        'name'      => 'No Contact Co',
                        'url'       => 'https://nc.example',
                        'relevance' => 'مرتبط',
                        'contact'   => ['email' => null, 'phone' => null, 'whatsapp' => null],
                        'price'     => ['value' => null, 'currency' => null, 'note' => null],
                    ],
                ],
                'summary' => 's',
            ],
        ]);

        $html = $this->render($run);

        $this->assertStringContainsString('No Contact Co', $html);
        // No mailto/tel links when contact is fully null.
        $this->assertStringNotContainsString('mailto:', $html);
        $this->assertStringNotContainsString('tel:', $html);
        // Em-dash appears for the empty contact/price rows.
        $this->assertStringContainsString('—', $html);
    }

    public function test_it_still_renders_old_price_hint_shape(): void
    {
        $run = new SourcingRun([
            'status'  => 'completed',
            'results' => [
                'suppliers' => [
                    [
                        'name'       => 'Legacy Supplier',
                        'url'        => 'https://legacy.example',
                        'relevance'  => 'قدیمی',
                        'price_hint' => 'حدود ۱۰۰ دلار',
                    ],
                ],
                'summary' => 'قدیمی',
            ],
        ]);

        $html = $this->render($run);

        $this->assertStringContainsString('Legacy Supplier', $html);
        $this->assertStringContainsString('حدود ۱۰۰ دلار', $html);
        // Old shape must NOT emit contact links.
        $this->assertStringNotContainsString('mailto:', $html);
    }

    public function test_it_renders_failed_run_error(): void
    {
        $run = new SourcingRun([
            'status' => 'failed',
            'error'  => 'Tavily search failed [500]',
        ]);

        $html = $this->render($run);

        $this->assertStringContainsString('Tavily search failed [500]', $html);
    }
}
