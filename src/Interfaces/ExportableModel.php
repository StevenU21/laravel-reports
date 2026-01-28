<?php

namespace Deifhelt\LaravelReports\Interfaces;

interface ExportableModel
{
    /**
     * Get the filename to be used when exporting this model.
     * Should include the extension if specific, or generally just the base name.
     */
    public function getExportFilename(): string;
}
