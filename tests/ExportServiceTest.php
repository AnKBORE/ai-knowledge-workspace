<?php

declare(strict_types=1);

namespace App\Tests;

use App\Services\ExportService;
use PHPUnit\Framework\TestCase;

final class ExportServiceTest extends TestCase
{
    private ExportService $exporter;

    protected function setUp(): void
    {
        $this->exporter = new ExportService();
    }

    public function testMarkdownToHtmlEscapesAndFormats(): void
    {
        $html = $this->exporter->markdownToHtml("# Titre\n\n**Gras** et <script>alert(1)</script>\n\n* un\n* deux");

        $this->assertStringContainsString('<h1>Titre</h1>', $html);
        $this->assertStringContainsString('<strong>Gras</strong>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('<li>un</li>', $html);
        $this->assertStringContainsString('<li>deux</li>', $html);
    }

    public function testToPdfStartsWithPdfSignature(): void
    {
        $bytes = $this->exporter->toPdf('Rapport', "**Test**\n\n* point");

        $this->assertStringStartsWith('%PDF-', $bytes);
        $this->assertGreaterThan(1000, strlen($bytes));
    }

    public function testToDocxIsAZipWithDocumentXml(): void
    {
        $bytes = $this->exporter->toDocx('Rapport', 'Contenu **gras**');

        $this->assertStringStartsWith('PK', $bytes);

        $tmp = (string) tempnam(sys_get_temp_dir(), 'docxtest_');
        file_put_contents($tmp, $bytes);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($tmp) === true);
        $xml = (string) $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($tmp);

        $this->assertStringContainsString('Rapport', $xml);
        $this->assertStringContainsString('gras', $xml);
    }
}
