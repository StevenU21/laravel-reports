<?php

namespace Deifhelt\LaravelReports\Interfaces;

use Symfony\Component\HttpFoundation\Response;

interface PdfRenderer
{
    /**
     * Render and stream a PDF to the browser.
     */
    public function stream(
        string $view,
        array $data,
        string|array $paper,
        string $orientation,
        string $filename
    ): Response;

    /**
     * Render and download a PDF.
     */
    public function download(
        string $view,
        array $data,
        string|array $paper,
        string $orientation,
        string $filename
    ): Response;
}
