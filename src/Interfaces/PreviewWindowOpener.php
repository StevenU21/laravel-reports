<?php

namespace Deifhelt\LaravelReports\Interfaces;

interface PreviewWindowOpener
{
    /**
     * Open a PDF preview window.
     */
    public function openPdfWindow(string $route, array $params, string $title): void;
}
