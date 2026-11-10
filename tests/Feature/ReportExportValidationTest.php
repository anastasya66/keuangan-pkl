<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Services\JournalPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class ReportExportValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staff;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->staff = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);

        // Buat akun akuntansi standar
        $cash = ChartOfAccount::create([
            'account_code' => '1110',
            'account_name' => 'Kas Toko',
            'account_type' => 'assets',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        $equity = ChartOfAccount::create([
            'account_code' => '3100',
            'account_name' => 'Modal Toko',
            'account_type' => 'equity',
            'normal_balance' => 'credit',
            'is_active' => true,
        ]);

        $revenue = ChartOfAccount::create([
            'account_code' => '4100',
            'account_name' => 'Pendapatan Penjualan',
            'account_type' => 'revenue',
            'normal_balance' => 'credit',
            'is_active' => true,
        ]);

        $expense = ChartOfAccount::create([
            'account_code' => '5100',
            'account_name' => 'Beban Operasional',
            'account_type' => 'expenses',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        // Buat kategori, unit & produk
        $cat = Category::create(['name' => 'Minuman', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Botol', 'symbol' => 'btl', 'is_active' => true]);

        $this->product = Product::create([
            'category_id' => $cat->id,
            'unit_id' => $unit->id,
            'sku' => '00123', // Tes SKU dengan leading zeroes
            'barcode' => '089912345678', // Tes Barcode dengan leading zeroes
            'name' => 'Teh Botol Sosro 450ml',
            'purchase_price' => 3500,
            'average_cost' => 3500,
            'selling_price' => 5000,
            'stock' => 50,
            'min_stock' => 10,
            'is_active' => true,
        ]);

        // Buat jurnal akuntansi
        $postingService = app(JournalPostingService::class);
        $jSale = $postingService->createDraft([
            'journal_date' => now()->toDateString(),
            'description' => 'Penjualan Minuman',
            'items' => [
                ['account_id' => $cash->id, 'debit' => 100000, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 100000],
            ],
        ], $this->admin->id);
        $postingService->postJournal($jSale, $this->admin->id);

        // Buat mutasi persediaan
        InventoryMovement::create([
            'movement_code' => 'MOV-20261110-001',
            'product_id' => $this->product->id,
            'movement_type' => 'purchase',
            'quantity' => 50,
            'cost_price' => 3500,
            'stock_before' => 0,
            'stock_after' => 50,
            'reference_number' => 'PO-20261110-001',
            'movement_date' => now()->toDateString(),
            'created_by' => $this->admin->id,
            'description' => 'Kulakan teh botol dari supplier',
        ]);
    }

    /**
     * Helper untuk validasi file biner XLSX via ZipArchive.
     */
    protected function assertValidXlsx(string $binaryContent): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_test_');
        file_put_contents($tempFile, $binaryContent);

        $zip = new ZipArchive();
        $res = $zip->open($tempFile);
        $this->assertTrue($res === true, 'Berkas bukan merupakan arsip zip XLSX yang valid');

        // Pastikan struktur OpenXML spreadsheet ada
        $this->assertNotFalse($zip->locateName('[Content_Types].xml'), 'File XLSX tidak memiliki [Content_Types].xml');
        $this->assertNotFalse($zip->locateName('xl/workbook.xml'), 'File XLSX tidak memiliki xl/workbook.xml');

        $zip->close();
        @unlink($tempFile);
    }

    /**
     * Helper untuk validasi file PDF (Magic header %PDF-).
     */
    protected function assertValidPdf(string $binaryContent): void
    {
        $this->assertStringStartsWith('%PDF-', $binaryContent, 'Berkas bukan merupakan dokumen PDF valid');
    }

    /**
     * 1. Uji Export PDF untuk ke-5 Laporan.
     */
    public function test_all_five_pdf_exports_return_valid_pdf_structure(): void
    {
        $endpoints = [
            '/api/v1/reports/income-statement/export/pdf',
            '/api/v1/reports/balance-sheet/export/pdf',
            '/api/v1/reports/inventory/export/pdf',
            '/api/v1/reports/stock-movements/export/pdf',
            '/api/v1/inventory/valuation/export/pdf',
            '/api/v1/reports/inventory-valuation/export/pdf',
        ];

        foreach ($endpoints as $url) {
            $response = $this->actingAs($this->admin, 'sanctum')->get($url);

            $response->assertOk();
            $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
            $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
            $this->assertStringContainsString('.pdf', $response->headers->get('Content-Disposition'));
            $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
            $this->assertValidPdf($response->getContent());
        }
    }

    /**
     * 2. Uji Export Excel (.xlsx) untuk ke-5 Laporan.
     */
    public function test_all_five_excel_exports_return_valid_openxml_workbook(): void
    {
        $endpoints = [
            '/api/v1/reports/income-statement/export/excel',
            '/api/v1/reports/balance-sheet/export/excel',
            '/api/v1/reports/inventory/export/excel',
            '/api/v1/reports/stock-movements/export/excel',
            '/api/v1/inventory/valuation/export/excel',
            '/api/v1/reports/inventory-valuation/export/excel',
        ];

        foreach ($endpoints as $url) {
            $response = $this->actingAs($this->admin, 'sanctum')->get($url);

            $response->assertOk();
            $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
            $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
            $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
            $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));

            $content = $response->streamedContent();
            $this->assertValidXlsx($content);
        }
    }

    /**
     * 3. Uji Autentikasi Sanctum: Request tanpa token wajib ditolak (401).
     */
    public function test_unauthenticated_export_requests_are_rejected(): void
    {
        $endpoints = [
            '/api/v1/reports/income-statement/export/pdf',
            '/api/v1/reports/income-statement/export/excel',
            '/api/v1/reports/balance-sheet/export/pdf',
            '/api/v1/reports/balance-sheet/export/excel',
            '/api/v1/reports/inventory/export/pdf',
            '/api/v1/reports/inventory/export/excel',
            '/api/v1/reports/stock-movements/export/pdf',
            '/api/v1/reports/stock-movements/export/excel',
            '/api/v1/inventory/valuation/export/pdf',
            '/api/v1/inventory/valuation/export/excel',
        ];

        foreach ($endpoints as $url) {
            $this->getJson($url)->assertStatus(401);
        }
    }

    /**
     * 4. Uji Filter Ekspor Persediaan (category_id, stock_status, search).
     */
    public function test_inventory_export_filters(): void
    {
        // Filter kategori yang tidak ada -> dataset kosong
        $resp = $this->actingAs($this->admin, 'sanctum')
            ->get('/api/v1/reports/inventory/export/excel?category_id=9999');
        $resp->assertOk();
        $this->assertValidXlsx($resp->streamedContent());

        // Filter stok status yang sesuai
        $resp2 = $this->actingAs($this->admin, 'sanctum')
            ->get('/api/v1/reports/inventory/export/pdf?stock_status=available');
        $resp2->assertOk();
        $this->assertValidPdf($resp2->getContent());
    }

    /**
     * 5. Uji Staff juga dapat mengakses Laporan & Export sesuai kebijakan aplikasi.
     */
    /**
     * 5. Uji Staff juga dapat mengakses Laporan & Export sesuai kebijakan aplikasi.
     */
    public function test_staff_can_access_reports_and_export(): void
    {
        $resp = $this->actingAs($this->staff, 'sanctum')
            ->get('/api/v1/reports/inventory/export/excel');
        $resp->assertOk();
        $this->assertValidXlsx($resp->streamedContent());
    }

    /**
     * 6. Uji Konfigurasi CORS: Header Content-Disposition wajib di-expose.
     */
    public function test_cors_exposes_content_disposition_header(): void
    {
        $exposedHeaders = config('cors.exposed_headers', []);
        $this->assertContains('Content-Disposition', $exposedHeaders, 'Header Content-Disposition harus di-expose pada config/cors.php agar Axios dapat membaca nama file');
    }
}

