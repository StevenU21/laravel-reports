<?php

namespace Deifhelt\LaravelReports\Preview;

use Deifhelt\LaravelReports\Interfaces\PreviewWindowOpener;
use Deifhelt\LaravelReports\Interfaces\ReportDefinition;
use Deifhelt\LaravelReports\LaravelReports;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreviewWindowReportManager
{
    public function __construct(
        private readonly LaravelReports $reports,
        private readonly PreviewWindowOpener $opener,
    ) {
    }

    /**
     * If the request wants preview/stream, returns the PDF Response (stream).
     * Otherwise validates (optional) and opens a preview window, returning 204.
     *
     * @throws \Deifhelt\LaravelReports\Exceptions\ReportException
     */
    public function process(
        ReportDefinition $report,
        Request $request,
        string $title,
        string $route,
        array $extraParams = [],
    ): Response {
        if ($this->wantsPreview($request)) {
            return $this->reports->process($report, $request, $title);
        }

        if ($report->shouldValidateLimit()) {
            $this->reports->validateQuery($report->query($request));
        }

        $params = array_merge(
            $this->safeRouteParams($request),
            $request->query(),
            $extraParams,
            ['preview' => 1]
        );

        $this->opener->openPdfWindow($route, $params, $title);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    private function wantsPreview(Request $request): bool
    {
        return $request->has('preview') || $request->has('stream');
    }

    /**
     * Best-effort route params extraction.
     *
     * - Scalars are included as-is
     * - Objects with getRouteKey() are converted to route keys
     * - Everything else is ignored to avoid leaking complex objects
     */
    private function safeRouteParams(Request $request): array
    {
        $route = $request->route();
        if (! $route) {
            return [];
        }

        $params = [];
        foreach (($route->parameters() ?? []) as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $params[$key] = $value;
                continue;
            }

            if (is_object($value) && method_exists($value, 'getRouteKey')) {
                $params[$key] = $value->getRouteKey();
            }
        }

        return $params;
    }
}
