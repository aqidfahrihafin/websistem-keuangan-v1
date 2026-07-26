<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class KartuCardService
{
    /**
     * $preview=true streams the PDF inline (Content-Disposition: inline) so
     * the browser renders it directly in a new tab instead of forcing a
     * save dialog - lets an admin check a card looks right before actually
     * printing it. Same document either way, just how the browser is told
     * to handle the response.
     */
    public function generate(Collection $kartus, string $filename, bool $preview = false): Response
    {
        $pdf = Pdf::loadView('pdf.kartu-santri', [
            'kartus' => $kartus,
            'texturePlate' => $this->batikTexture('#ffffff', 0.09),
            'textureLight' => $this->batikTexture('#0f766e', 0.055),
        ])
            ->setPaper('a4', 'portrait');

        return $preview ? $pdf->stream($filename) : $pdf->download($filename);
    }

    /**
     * A small "kawung"-style offset dot grid, sized to exactly cover one
     * card (85.6 x 53.98mm). dompdf's CSS engine silently drops
     * linear-/radial-gradient() wherever they're used (confirmed by direct
     * render testing), including as a repeating background-image, so a
     * tiled dot texture can't be done in CSS here - it's baked once into a
     * single SVG <img> instead, which dompdf's SVG image renderer handles
     * fine. Computed once per PDF (not per card) since every card shares
     * the same fixed size.
     */
    private function batikTexture(string $color, float $opacity): string
    {
        $circles = '';
        foreach (range(0, 9) as $row) {
            $offset = $row % 2 === 1 ? 35 : 0;
            foreach (range(0, 13) as $col) {
                $x = $col * 70 + $offset;
                $y = $row * 54 + 20;
                $circles .= "<circle cx=\"{$x}\" cy=\"{$y}\" r=\"4\" fill=\"{$color}\" fill-opacity=\"{$opacity}\" />";
            }
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 856 540">'.$circles.'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
