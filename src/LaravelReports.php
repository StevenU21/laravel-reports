<?php

namespace Deifhelt\LaravelReports;

use Deifhelt\LaravelReports\Exceptions\ReportException;
use Deifhelt\LaravelReports\Interfaces\PdfRenderer;
use Deifhelt\LaravelReports\Interfaces\ReportDefinition;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LaravelReports
{
    public function __construct(private readonly PdfRenderer $pdfRenderer)
    {
    }

    /**
     * Process a report request (Stream or Download).
     *
     * @param  string  $title  Optional title (used mostly for metadata or window title in other contexts)
     *
     * @throws ReportException
     */
    public function process(ReportDefinition $report, Request $request, string $title = 'Report'): Response
    {
        $query = $report->query($request);

        // Validation logic
        if ($report->shouldValidateLimit()) {
            $this->validateQuery($query);
        }

        // Decide between stream or download based on request input
        // Default to download unless 'preview' or 'stream' is present
        if ($request->has('preview') || $request->has('stream')) {
            return $this->generatePdfResponse($report, $request, 'stream', $query, $title);
        }

        return $this->generatePdfResponse($report, $request, 'download', $query, $title);
    }

    /**
     * Validate the query result size.
     *
     * @throws ReportException
     */
    public function validateQuery(mixed $query): void
    {
        $count = 0;

        // Handle Eloquent Builder, Query Builder, or Relation
        if (
            $query instanceof \Illuminate\Database\Eloquent\Builder ||
            $query instanceof \Illuminate\Database\Query\Builder ||
            $query instanceof \Illuminate\Database\Eloquent\Relations\Relation
        ) {

            // Optimization for builders
            try {
                $count = $query->toBase()->getCountForPagination();
            } catch (\Throwable $e) {
                $count = $query->count();
            }
        } elseif ($query instanceof \Illuminate\Support\Collection || is_array($query)) {
            $count = count($query);
        } else {
            // Fallback
            if (method_exists($query, 'count')) {
                $count = $query->count();
            }
        }

        if ($count === 0) {
            throw new ReportException(
                'No data available to generate the report with the selected filters.'
            );
        }

        $limit = config('reports.limit', 1000);

        if ($count > $limit) {
            throw new ReportException(
                'The report exceeds the allowed limit of '.number_format($limit).' records. Please apply more filters.'
            );
        }
    }

    /**
     * Stream the PDF to the browser.
     */
    public function stream(ReportDefinition $definition, Request $request): Response
    {
        return $this->generatePdfResponse($definition, $request, 'stream');
    }

    /**
     * Download the PDF.
     */
    public function download(ReportDefinition $definition, Request $request): Response
    {
        return $this->generatePdfResponse($definition, $request, 'download');
    }

    /**
     * Internal method to generate PDF response.
     */
    protected function generatePdfResponse(
        ReportDefinition $definition,
        Request $request,
        string $type,
        mixed $query = null,
        string $title = 'Report'
    ): Response
    {
        $query = $query ?? $definition->query($request);

        $data = $query;
        if (
            $query instanceof \Illuminate\Database\Eloquent\Builder ||
            $query instanceof \Illuminate\Database\Query\Builder ||
            $query instanceof \Illuminate\Database\Eloquent\Relations\Relation
        ) {
            $data = $query->get();
        }

        $viewData = [
            'data' => $data,
            // Only pass query-string filters to avoid leaking body/file inputs into the PDF
            'filters' => $request->query(),
            'title' => $title,
        ];

        // Add summary if exists
        if (method_exists($definition, 'summary')) {
            $viewData['summary'] = $definition->summary($data instanceof \Illuminate\Support\Collection ? $data : collect([$data]));
            $viewData['totals'] = $viewData['summary'];
        }

        // Add extra view data
        if (method_exists($definition, 'viewData')) {
            // Handle if data is single model vs collection
            $currData = $data instanceof \Illuminate\Support\Collection ? $data : collect([$data]);
            $viewData = array_merge($viewData, $definition->viewData($currData));
        } else {
            // Default trait might handle this, but if not using trait:
            $viewData['data'] = $data;
        }

        // Handle extraData from definition
        if (method_exists($definition, 'extraData')) {
            $viewData = array_merge($viewData, $definition->extraData($request));
        }

        $paper = 'letter';
        if (method_exists($definition, 'paper')) {
            $paper = $definition->paper();
        }

        $orientation = 'portrait';
        if (method_exists($definition, 'orientation')) {
            $orientation = $definition->orientation();
        }

        return $type === 'stream'
            ? $this->pdfRenderer->stream($definition->view(), $viewData, $paper, $orientation, $definition->filename())
            : $this->pdfRenderer->download($definition->view(), $viewData, $paper, $orientation, $definition->filename());
    }
}
