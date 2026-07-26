<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use DOMDocument;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use ZipArchive;

/**
 * Generates the PRD (Product Requirements Document) on demand from a single
 * source of truth (`resources/views/pdf/prd.blade.php`) - both the PDF and
 * the editable Word version are always derived fresh from that same
 * template, so they can never drift out of sync with each other the way two
 * separately hand-maintained files would.
 */
class PrdDocumentService
{
    public function pdf(): DomPdf
    {
        return Pdf::loadView('pdf.prd')->setPaper('a4', 'portrait');
    }

    /**
     * Returns the path to a freshly-generated .docx in a temp location -
     * caller is responsible for deleting it after use (mis.
     * response()->download(...)->deleteFileAfterSend()).
     */
    public function docxPath(): string
    {
        $html = view('pdf.prd')->render();

        // PhpWord's HTML importer parses via DOMDocument::loadXML(), which
        // requires well-formed XML - our source is plain HTML5 (unclosed
        // <meta>/<br> etc.), so it's round-tripped through DOMDocument's
        // lenient loadHTML() + saveXML() first to get a well-formed XHTML
        // string PhpWord can actually parse.
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NOBLANKS);
        libxml_clear_errors();
        $xhtml = $dom->saveXML($dom->documentElement);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection();
        Html::addHtml($section, $xhtml, true, false);
        // Word refuses to open a .docx whose body ends directly on a table
        // (reports the file as corrupted) - the PRD's last section
        // (Lampiran B) is a table, so without a trailing paragraph after
        // it the generated file would be unopenable.
        $section->addTextBreak();

        $path = tempnam(sys_get_temp_dir(), 'prd_').'.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        $this->repairAmpersands($path);

        return $path;
    }

    /**
     * Works around a PhpWord ^1.4 bug (confirmed via isolated repro, not
     * specific to this template): its HTML importer reads DOMText::nodeValue
     * (already entity-decoded by the DOM API - "web &amp; mobile" becomes
     * the literal string "web & mobile") and writes that raw decoded text
     * straight into a <w:t> run without re-escaping "&", producing invalid
     * XML. Every literal "&" in the source HTML makes the resulting
     * document.xml malformed, and Word reports the whole file as corrupted
     * rather than just that character. Repaired here by re-opening the
     * written .docx (itself just a zip) and escaping any bare "&" in
     * document.xml that isn't already part of a valid XML entity.
     */
    private function repairAmpersands(string $path): void
    {
        $zip = new ZipArchive();
        $zip->open($path);

        $xml = $zip->getFromName('word/document.xml');
        $xml = preg_replace('/&(?!amp;|lt;|gt;|quot;|apos;|#\d+;|#x[0-9a-fA-F]+;)/', '&amp;', $xml);

        $zip->deleteName('word/document.xml');
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();
    }
}
