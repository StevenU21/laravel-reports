<?php

namespace Deifhelt\LaravelReports;

use Barryvdh\DomPDF\Facade\Pdf;
use Deifhelt\LaravelReports\Exceptions\ReportException;
use Deifhelt\LaravelReports\Interfaces\ReportDefinition;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LaravelReports
{
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
            return $this->stream($report, $request);
        }

        return $this->download($report, $request);
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
    protected function generatePdfResponse(ReportDefinition $definition, Request $request, string $type): Response
    {
        $query = $definition->query($request);

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
            'filters' => $request->all(),
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

        $pdf = Pdf::loadView($definition->view(), $viewData)
            ->setPaper($paper, $orientation);

        return $type === 'stream'
            ? $pdf->stream($definition->filename())
            : $pdf->download($definition->filename());
    }
}
