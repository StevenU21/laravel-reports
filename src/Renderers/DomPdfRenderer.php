<?php

namespace Deifhelt\LaravelReports\Renderers;

use Barryvdh\DomPDF\Facade\Pdf;
use Deifhelt\LaravelReports\Interfaces\PdfRenderer;
use Symfony\Component\HttpFoundation\Response;

class DomPdfRenderer implements PdfRenderer
{
    public function stream(
        string $view,
        array $data,
        string|array $paper,
        string $orientation,
        string $filename
    ): Response {
        return Pdf::loadView($view, $data)
            ->setPaper($paper, $orientation)
            ->stream($filename);
    }

    public function download(
        string $view,
        array $data,
        string|array $paper,
        string $orientation,
        string $filename
    ): Response {
        return Pdf::loadView($view, $data)
            ->setPaper($paper, $orientation)
            ->download($filename);
    }
}
