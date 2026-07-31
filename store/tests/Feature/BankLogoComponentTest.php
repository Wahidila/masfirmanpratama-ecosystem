<?php

namespace Tests\Feature;

use Tests\TestCase;

class BankLogoComponentTest extends TestCase
{
    public function test_renders_svg_logo_when_slug_has_asset(): void
    {
        $view = $this->blade('<x-bank-logo :bank="$bank" />', [
            'bank' => ['bank' => 'BCA', 'logo' => 'bca', 'logo_color' => 'sky'],
        ]);

        $view->assertSee('<img', false);
        $view->assertSee('images/bank-logos/bca.svg', false);
    }

    public function test_falls_back_to_initials_badge_when_no_logo(): void
    {
        $view = $this->blade('<x-bank-logo :bank="$bank" />', [
            'bank' => ['bank' => 'Bank Kustom', 'logo' => '', 'logo_color' => 'amber'],
        ]);

        $view->assertDontSee('<img', false);
        $view->assertSee('Bank Kustom');
        $view->assertSee('bg-amber-50', false);
    }

    public function test_falls_back_to_badge_when_slug_has_no_asset_file(): void
    {
        $view = $this->blade('<x-bank-logo :bank="$bank" />', [
            'bank' => ['bank' => 'Ghost Bank', 'logo' => 'nonexistent-bank', 'logo_color' => 'rose'],
        ]);

        $view->assertDontSee('<img', false);
        $view->assertSee('Ghost Bank');
    }
}
