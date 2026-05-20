<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UploadProofPageTest extends TestCase
{
    use RefreshDatabase;

    private const ORDER = 'MFP-20260516-ABC123';

    // ─── GET /upload/{order_number} ─────────────────────────────────────────

    public function test_upload_page_returns_200(): void
    {
        $this->get('/upload/'.self::ORDER)->assertStatus(200);
    }

    public function test_upload_page_uses_store_layout_assets(): void
    {
        $response = $this->get('/upload/'.self::ORDER);

        $response->assertStatus(200);
        // Vite-injected CSS + JS markers
        $response->assertSeeInOrder(['/build/assets/app-', '.css'], false);
        $response->assertSeeInOrder(['/build/assets/app-', '.js'], false);
        $response->assertSee('csrf-token', false);
        $response->assertSee('unpkg.com/lucide', false);
        // Page chrome
        $response->assertSee('Upload Bukti Bayar', false);
        $response->assertSee('Kirim bukti transfer', false);
    }

    public function test_upload_page_renders_order_number_in_header(): void
    {
        $response = $this->get('/upload/'.self::ORDER);

        $response->assertStatus(200);
        $response->assertSee('data-testid="upload-order-number"', false);
        $response->assertSee(self::ORDER, false);
    }

    public function test_upload_page_renders_total_transfer_when_provided_via_query(): void
    {
        // Lunas — query string carries total nominal that customer harus transfer.
        $response = $this->get('/upload/'.self::ORDER.'?type=lunas&total=4525000');

        $response->assertStatus(200);
        $response->assertSee('Total Transfer (Lunas)', false);
        $response->assertSee('Rp 4.525.000', false);
        $response->assertDontSee('Nominal Pembayaran (DP / Cicilan)', false);
    }

    public function test_upload_page_renders_dp_label_when_payment_type_cicilan(): void
    {
        // Cicilan 3x → DP nominal di query.
        $response = $this->get('/upload/'.self::ORDER.'?type=cicilan&total=1357500&n=3&seq=0');

        $response->assertStatus(200);
        $response->assertSee('Nominal Pembayaran (DP / Cicilan)', false);
        $response->assertSee('Rp 1.357.500', false);
        $response->assertDontSee('Total Transfer (Lunas)', false);
    }

    public function test_upload_page_renders_form_with_strict_accept_attribute(): void
    {
        $response = $this->get('/upload/'.self::ORDER);

        $response->assertStatus(200);
        $response->assertSee('data-testid="upload-form"', false);
        $response->assertSee('enctype="multipart/form-data"', false);
        $response->assertSee('id="proof_file"', false);
        $response->assertSee('name="proof_file"', false);
        // Accept exactly the 3 supported image MIME types — strict per task spec.
        $response->assertSee('accept="image/jpeg,image/png,image/webp"', false);
    }

    public function test_upload_page_renders_inline_validation_messages(): void
    {
        $response = $this->get('/upload/'.self::ORDER);

        $response->assertStatus(200);
        // FE strict validation: type + size, error message inline.
        $response->assertSee('data-testid="upload-file-error"', false);
        // Constraint copy supaya user tahu max 2MB before submit.
        $response->assertSee('Maks. 2 MB', false);
        $response->assertSee('JPG, PNG, atau WebP', false);
    }

    public function test_upload_page_renders_dropzone_and_preview_targets(): void
    {
        $response = $this->get('/upload/'.self::ORDER);

        $response->assertStatus(200);
        $response->assertSee('data-testid="upload-dropzone"', false);
        $response->assertSee('data-testid="upload-preview"', false);
        // Submit button wired to controlled disabled state.
        $response->assertSee('data-testid="upload-submit"', false);
        $response->assertSee('Kirim bukti bayar', false);
    }

    public function test_upload_page_lunas_renders_disabled_installment_dropdown(): void
    {
        $response = $this->get('/upload/'.self::ORDER.'?type=lunas&total=4525000');

        $response->assertStatus(200);
        $response->assertSee('data-testid="installment-select"', false);
        // Lunas → placeholder "sekali bayar" + dropdown disabled.
        $response->assertSee('Pembayaran Lunas (sekali bayar)', false);
        $response->assertSee('disabled', false);
        // Hidden input supaya backend tetap dapat field.
        $response->assertSee('name="installment_sequence" value="0"', false);
    }

    public function test_upload_page_cicilan_renders_dynamic_installment_options(): void
    {
        // 4x cicilan → 1 DP + 3 cicilan = 4 opsi.
        $response = $this->get('/upload/'.self::ORDER.'?type=cicilan&total=1131250&n=4&seq=0');

        $response->assertStatus(200);
        $response->assertSee('data-testid="installment-select"', false);
        $response->assertSee('Down Payment (DP)', false);
        $response->assertSee('Cicilan ke-1 dari 3', false);
        $response->assertSee('Cicilan ke-2 dari 3', false);
        $response->assertSee('Cicilan ke-3 dari 3', false);
        // Cicilan terakhir di-flag.
        $response->assertSee('Cicilan terakhir', false);
        // Lunas placeholder TIDAK boleh muncul untuk cicilan.
        $response->assertDontSee('Pembayaran Lunas (sekali bayar)', false);
    }

    public function test_upload_page_form_posts_to_upload_store_route(): void
    {
        $response = $this->get('/upload/'.self::ORDER);

        $response->assertStatus(200);
        $response->assertSee('action="'.route('upload.store', ['order_number' => self::ORDER]).'"', false);
        $response->assertSee('method="POST"', false);
    }

    public function test_upload_page_links_back_to_checkout_success(): void
    {
        $response = $this->get('/upload/'.self::ORDER);

        $response->assertStatus(200);
        $response->assertSee('href="'.route('checkout.success', ['order' => self::ORDER]).'"', false);
        $response->assertSee('Lihat info rekening lagi', false);
    }

    public function test_upload_page_includes_security_todo_for_m2(): void
    {
        // Catatan keamanan task spec: route WAJIB di-token-protect di M2,
        // tinggalkan TODO comment yang gampang dicari saat M2 admin landing.
        $response = $this->get('/upload/'.self::ORDER);

        $response->assertStatus(200);
        // Comment Blade dirender ke HTML output; pastikan keyword keamanan ada.
        // Worst case kalau Blade strip comment, fallback: cek view file langsung.
        $viewPath = base_path('resources/views/pages/upload.blade.php');
        $this->assertFileExists($viewPath);
        $contents = file_get_contents($viewPath);
        $this->assertStringContainsString('TODO', $contents);
        $this->assertStringContainsString('token-protect', $contents);
        $this->assertStringContainsString('signed URL', $contents);
    }

    // ─── POST /upload/{order_number} (M1 stub) ──────────────────────────────

    public function test_upload_post_accepts_valid_image_and_redirects_with_success(): void
    {
        $file = UploadedFile::fake()->image('bukti.jpg', 800, 600)->size(500); // 500 KB

        $response = $this->post('/upload/'.self::ORDER, [
            'installment_sequence' => 0,
            'proof_file' => $file,
            'note' => 'Transfer dari BCA jam 14:32',
        ]);

        $response->assertStatus(302);
        $response->assertRedirectContains('/upload/'.self::ORDER);
        $response->assertSessionHas('upload.success', true);
        $response->assertSessionHas('upload.sequence', 0);
    }

    public function test_upload_post_rejects_oversized_file(): void
    {
        // 3 MB → over 2 MB limit.
        $file = UploadedFile::fake()->image('bukti.jpg', 4000, 3000)->size(3072);

        $response = $this->from('/upload/'.self::ORDER)->post('/upload/'.self::ORDER, [
            'installment_sequence' => 0,
            'proof_file' => $file,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/upload/'.self::ORDER);
        $response->assertSessionHasErrors('proof_file');
    }

    public function test_upload_post_rejects_disallowed_mime_type(): void
    {
        // PDF — not in whitelist.
        $file = UploadedFile::fake()->create('bukti.pdf', 200, 'application/pdf');

        $response = $this->from('/upload/'.self::ORDER)->post('/upload/'.self::ORDER, [
            'installment_sequence' => 0,
            'proof_file' => $file,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/upload/'.self::ORDER);
        $response->assertSessionHasErrors('proof_file');
    }

    public function test_upload_post_requires_proof_file(): void
    {
        $response = $this->from('/upload/'.self::ORDER)->post('/upload/'.self::ORDER, [
            'installment_sequence' => 0,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/upload/'.self::ORDER);
        $response->assertSessionHasErrors('proof_file');
    }

    public function test_upload_show_renders_success_state_when_session_flag_present(): void
    {
        $file = UploadedFile::fake()->image('bukti.png', 800, 600)->size(300);

        $response = $this->followingRedirects()->post('/upload/'.self::ORDER, [
            'installment_sequence' => 0,
            'proof_file' => $file,
        ]);

        $response->assertStatus(200);
        // Success state visible (the Alpine x-show flips via initialSuccess flag
        // serialized into the page config).
        $response->assertSee('data-testid="upload-success-state"', false);
        $response->assertSee('Bukti diterima', false);
        $response->assertSee('admin akan verifikasi pembayaran', false);
        // Initial success flag must be true in the rendered Alpine config.
        // @js() encodes the value but the surrounding object literal stays JS-shape.
        $response->assertSee('initialSuccess: true', false);
        $response->assertDontSee('initialSuccess: false', false);
    }
}
