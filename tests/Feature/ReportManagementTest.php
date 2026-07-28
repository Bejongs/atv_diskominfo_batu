<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_report_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Report Center')
            ->assertSee('Parameter Laporan')
            ->assertSee('Distribusi Kategori');
    }

    public function test_user_can_export_report_to_xlsx_and_pdf(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('archives.store'), [
            'title' => 'Berita Kota Batu',
            'description' => 'Materi berita harian.',
            'category' => 'News',
            'issue' => 'Ekonomi',
            'status' => 'Siap Tayang',
            'air_date' => '2026-07-20',
            'video_url' => 'https://example.com/video/laporan',
            'video' => [
                UploadedFile::fake()->create('berita.mp4', 1024, 'video/mp4'),
            ],
        ]);

        $payload = [
            'title' => 'Laporan Arsip Video ATV',
            'category' => 'News',
            'issue' => 'Ekonomi',
            'status' => 'Siap Tayang',
            'format' => 'xlsx',
        ];

        $xlsxResponse = $this->actingAs($user)->post(route('reports.export'), $payload);
        $xlsxResponse->assertOk();
        $xlsxResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $xlsxResponse->getContent());
        $this->assertStringContainsString('Berita Kota Batu', $xlsxResponse->getContent());
        $this->assertStringContainsString('Diskominfo Kota Batu', $xlsxResponse->getContent());
        $this->assertStringContainsString('Rating Usia', $xlsxResponse->getContent());
        $this->assertStringNotContainsString('<mergeCells', $xlsxResponse->getContent());
        $this->assertStringNotContainsString('Visualisasi', $xlsxResponse->getContent());
        $this->assertStringNotContainsString('xl/media/report-visual.png', $xlsxResponse->getContent());
        $this->assertStringNotContainsString('xl/drawings/drawing1.xml', $xlsxResponse->getContent());

        $pdfResponse = $this->actingAs($user)->post(route('reports.export'), array_merge($payload, ['format' => 'pdf']));
        $pdfResponse->assertOk();
        $pdfResponse->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdfResponse->getContent());
        $this->assertStringContainsString('Ukuran rata-rata', $pdfResponse->getContent());
        $this->assertStringContainsString('Distribusi Kategori', $pdfResponse->getContent());
    }
}
