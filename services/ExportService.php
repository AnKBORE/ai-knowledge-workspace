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
            body { font-family: "DejaVu Sans", sans-serif; font-size: 12px; color: #0f172a; }
            h1, h2, h3 { color: #1e3a8a; margin: 14px 0 6px; }
            h1 { font-size: 18px; } h2 { font-size: 15px; } h3 { font-size: 13px; }
            p { margin: 0 0 8px; line-height: 1.5; }
            ul { margin: 4px 0 10px 18px; padding: 0; }
            li { margin: 2px 0; line-height: 1.45; }
            strong { font-weight: bold; }
            .doc-title { font-size: 22px; font-weight: bold; color: #1e3a8a; margin-bottom: 16px; }
            .doc-footer { margin-top: 28px; font-size: 9px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 6px; }
        </style></head><body>'
            . '<div class="doc-title">' . $safeTitle . '</div>'
            . $this->markdownToHtml($content)
            . '<div class="doc-footer">Agentic Multimodal Knowledge Workspace · '
            . date('d/m/Y H:i') . '</div>'
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
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>' . $paragraphs
            . '<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134"/></w:sectPr>'
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
            . '</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:docDefaults><w:rPrDefault><w:rPr><w:sz w:val="22"/></w:rPr></w:rPrDefault></w:docDefaults>'
            . '<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:rPr><w:b/><w:sz w:val="40"/><w:color w:val="1E3A8A"/></w:rPr></w:style>'
            . '<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:rPr><w:b/><w:sz w:val="32"/><w:color w:val="1E3A8A"/></w:rPr></w:style>'
            . '<w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:rPr><w:b/><w:sz w:val="28"/><w:color w:val="1E3A8A"/></w:rPr></w:style>'
            . '</w:styles>';
    }
}
