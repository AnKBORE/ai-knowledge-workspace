<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use ZipArchive;

/**
 * Export a generated result (answer, draft, summary) as PDF or DOCX.
 *
 * The input is light Markdown: headings (#, ##, ###), bold (**…**) and
 * bullet lists (* or -). The converter is deliberately small and safe.
 */
final class ExportService
{
    public function toPdf(string $title, string $content): string
    {
        $dompdf = new Dompdf([
            'isRemoteEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
        ]);
        $dompdf->loadHtml($this->htmlDocument($title, $content), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return (string) $dompdf->output();
    }

    public function toDocx(string $title, string $content): string
    {
        $tmp = (string) tempnam(sys_get_temp_dir(), 'docx_');
        $zip = new ZipArchive();

        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create the DOCX archive.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->relsXml());
        $zip->addFromString('word/_rels/document.xml.rels', $this->documentRelsXml());
        $zip->addFromString('word/styles.xml', $this->stylesXml());
        $zip->addFromString('word/footer1.xml', $this->footerXml());
        $zip->addFromString('word/document.xml', $this->documentXml($title, $content));
        $zip->close();

        $bytes = (string) file_get_contents($tmp);
        @unlink($tmp);

        return $bytes;
    }

    /**
     * Convert the light Markdown to safe HTML.
     */
    public function markdownToHtml(string $markdown): string
    {
        $lines = preg_split('/\R/u', $markdown) ?: [];
        $html = '';
        $inList = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                if ($inList) {
                    $html .= '</ul>';
                    $inList = false;
                }
                continue;
            }

            if (preg_match('/^(#{1,3})\s+(.*)$/u', $trimmed, $m) === 1) {
                if ($inList) {
                    $html .= '</ul>';
                    $inList = false;
                }
                $level = strlen($m[1]);
                $html .= sprintf('<h%d>%s</h%d>', $level, $this->inline($m[2]), $level);
                continue;
            }

            if (preg_match('/^[*\-•]\s+(.*)$/u', $trimmed, $m) === 1) {
                if (!$inList) {
                    $html .= '<ul>';
                    $inList = true;
                }
                $html .= '<li>' . $this->inline($m[1]) . '</li>';
                continue;
            }

            if ($inList) {
                $html .= '</ul>';
                $inList = false;
            }
            $html .= '<p>' . $this->inline($trimmed) . '</p>';
        }

        if ($inList) {
            $html .= '</ul>';
        }

        return $html;
    }

    private function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return preg_replace('/\*\*(.+?)\*\*/su', '<strong>$1</strong>', $text) ?? $text;
    }

    private function htmlDocument(string $title, string $content): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<!doctype html><html lang="fr"><head><meta charset="utf-8"><style>
            @page { margin: 22mm 20mm 24mm; }
            body { font-family: "DejaVu Sans", sans-serif; font-size: 11.5px; color: #0f172a; }
            .doc-eyebrow { font-size: 8.5px; letter-spacing: 1.6px; text-transform: uppercase;
                           color: #4a63c8; font-weight: bold; margin-bottom: 4px; }
            .doc-title { font-size: 23px; font-weight: bold; color: #1e3a8a; margin: 0 0 6px; }
            .doc-rule { border-bottom: 2.5px solid #4a63c8; width: 64px; margin-bottom: 10px; }
            .doc-meta { font-size: 9px; color: #64748b; margin-bottom: 20px; }
            h1, h2, h3 { color: #1e3a8a; margin: 16px 0 6px; page-break-after: avoid; }
            h1 { font-size: 17px; border-bottom: 1px solid #dbe3f5; padding-bottom: 4px; }
            h2 { font-size: 14px; }
            h3 { font-size: 12.5px; }
            p { margin: 0 0 8px; line-height: 1.55; text-align: justify; }
            ul { margin: 4px 0 10px 18px; padding: 0; }
            li { margin: 3px 0; line-height: 1.5; text-align: justify; }
            li::marker { color: #4a63c8; }
            strong { font-weight: bold; }
            .doc-footer { position: fixed; bottom: -14mm; left: 0; right: 0;
                          font-size: 8px; color: #64748b; border-top: 1px solid #e2e8f0;
                          padding-top: 5px; }
            .doc-footer .right { float: right; }
        </style></head><body>'
            . '<div class="doc-eyebrow">Agentic Knowledge Workspace</div>'
            . '<div class="doc-title">' . $safeTitle . '</div>'
            . '<div class="doc-rule"></div>'
            . '<div class="doc-meta">Generated on ' . date('d/m/Y \a\t H:i') . '</div>'
            . $this->markdownToHtml($content)
            . '<div class="doc-footer">Agentic Knowledge Workspace'
            . '<span class="right">Page {PAGE_NUM} / {PAGE_COUNT}</span></div>'
            . '<script type="text/php">if (isset($pdf)) { $text = "Page " . $PAGE_NUM . " / " . $PAGE_COUNT; }</script>'
            . '</body></html>';
    }

    private function documentXml(string $title, string $content): string
    {
        $paragraphs = $this->docxParagraph($title, 'Title');

        foreach (preg_split('/\R/u', $content) ?: [] as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^(#{1,3})\s+(.*)$/u', $trimmed, $m) === 1) {
                $paragraphs .= $this->docxParagraph($m[2], strlen($m[1]) === 1 ? 'Heading1' : 'Heading2');
            } elseif (preg_match('/^[*\-•]\s+(.*)$/u', $trimmed, $m) === 1) {
                $paragraphs .= $this->docxParagraph('• ' . $m[1], null);
            } else {
                $paragraphs .= $this->docxParagraph($trimmed, null);
            }
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<w:body>' . $paragraphs
            . '<w:sectPr>'
            . '<w:footerReference w:type="default" r:id="rId2"/>'
            . '<w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/>'
            . '</w:sectPr>'
            . '</w:body></w:document>';
    }

    private function docxParagraph(string $text, ?string $style): string
    {
        $styleXml = $style !== null ? '<w:pPr><w:pStyle w:val="' . $style . '"/></w:pPr>' : '';

        return '<w:p>' . $styleXml . $this->docxRuns($text) . '</w:p>';
    }

    /** Split **bold** segments into runs. */
    private function docxRuns(string $text): string
    {
        $parts = preg_split('/\*\*(.+?)\*\*/su', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
        $xml = '';

        foreach ($parts as $index => $part) {
            if ($part === '') {
                continue;
            }
            $bold = $index % 2 === 1;
            $xml .= '<w:r>' . ($bold ? '<w:rPr><w:b/></w:rPr>' : '')
                . '<w:t xml:space="preserve">'
                . htmlspecialchars($part, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                . '</w:t></w:r>';
        }

        return $xml;
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . '<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>'
            . '</Types>';
    }

    private function relsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '</Relationships>';
    }

    private function documentRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/>'
            . '</Relationships>';
    }

    private function stylesXml(): string
    {
        $font = '<w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:docDefaults><w:rPrDefault><w:rPr>' . $font . '<w:sz w:val="23"/><w:color w:val="0F172A"/></w:rPr></w:rPrDefault>'
            . '<w:pPrDefault><w:pPr><w:spacing w:after="160" w:line="276" w:lineRule="auto"/><w:jc w:val="both"/></w:pPr></w:pPrDefault></w:docDefaults>'
            . '<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/>'
            . '<w:pPr><w:spacing w:after="120"/><w:pBdr><w:bottom w:val="single" w:sz="18" w:space="6" w:color="4A63C8"/></w:pBdr></w:pPr>'
            . '<w:rPr>' . $font . '<w:b/><w:sz w:val="48"/><w:color w:val="1E3A8A"/></w:rPr></w:style>'
            . '<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/>'
            . '<w:pPr><w:spacing w:before="320" w:after="120"/><w:keepNext/><w:pBdr><w:bottom w:val="single" w:sz="6" w:space="3" w:color="DBE3F5"/></w:pBdr></w:pPr>'
            . '<w:rPr>' . $font . '<w:b/><w:sz w:val="34"/><w:color w:val="1E3A8A"/></w:rPr></w:style>'
            . '<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/>'
            . '<w:pPr><w:spacing w:before="240" w:after="80"/><w:keepNext/></w:pPr>'
            . '<w:rPr>' . $font . '<w:b/><w:sz w:val="28"/><w:color w:val="1E3A8A"/></w:rPr></w:style>'
            . '</w:styles>';
    }

    private function footerXml(): string
    {
        $date = htmlspecialchars(date('d/m/Y'), ENT_XML1, 'UTF-8');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:p><w:pPr><w:pBdr><w:top w:val="single" w:sz="4" w:space="4" w:color="E2E8F0"/></w:pBdr>'
            . '<w:tabs><w:tab w:val="right" w:pos="9638"/></w:tabs><w:jc w:val="both"/></w:pPr>'
            . '<w:r><w:rPr><w:sz w:val="16"/><w:color w:val="64748B"/></w:rPr>'
            . '<w:t xml:space="preserve">Agentic Knowledge Workspace · ' . $date . '</w:t></w:r>'
            . '<w:r><w:tab/></w:r>'
            . '<w:r><w:rPr><w:sz w:val="16"/><w:color w:val="64748B"/></w:rPr><w:t xml:space="preserve">Page </w:t></w:r>'
            . '<w:r><w:rPr><w:sz w:val="16"/><w:color w:val="64748B"/></w:rPr><w:fldChar w:fldCharType="begin"/></w:r>'
            . '<w:r><w:rPr><w:sz w:val="16"/></w:rPr><w:instrText xml:space="preserve"> PAGE </w:instrText></w:r>'
            . '<w:r><w:fldChar w:fldCharType="end"/></w:r>'
            . '<w:r><w:rPr><w:sz w:val="16"/><w:color w:val="64748B"/></w:rPr><w:t xml:space="preserve"> / </w:t></w:r>'
            . '<w:r><w:rPr><w:sz w:val="16"/></w:rPr><w:fldChar w:fldCharType="begin"/></w:r>'
            . '<w:r><w:rPr><w:sz w:val="16"/></w:rPr><w:instrText xml:space="preserve"> NUMPAGES </w:instrText></w:r>'
            . '<w:r><w:fldChar w:fldCharType="end"/></w:r>'
            . '</w:p></w:ftr>';
    }
}
