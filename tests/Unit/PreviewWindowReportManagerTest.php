<?php

use Deifhelt\LaravelReports\Interfaces\PreviewWindowOpener;
use Deifhelt\LaravelReports\Interfaces\ReportDefinition;
use Deifhelt\LaravelReports\LaravelReports;
use Deifhelt\LaravelReports\Preview\PreviewWindowReportManager;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;

it('opens a preview window after validating limit', function () {
    /** @var LaravelReports|MockInterface $reports */
    $reports = Mockery::mock(LaravelReports::class);
    /** @var PreviewWindowOpener|MockInterface $opener */
    $opener = Mockery::mock(PreviewWindowOpener::class);

    $report = new class implements ReportDefinition {
        public function query(Request $request)
        {
            return collect([1, 2, 3]);
        }

        public function view(): string
        {
            return 'x';
        }

        public function filename(): string
        {
            return 'x.pdf';
        }

        public function shouldValidateLimit(): bool
        {
            return true;
        }
    };

    $request = Request::create('/exports/sales', 'GET', ['status' => 'paid']);

    $reports->shouldReceive('validateQuery')->once();
    $opener->shouldReceive('openPdfWindow')
        ->with(
            'exports.sales.stream',
            Mockery::on(function (array $params) {
                return ($params['status'] ?? null) === 'paid'
                    && ($params['preview'] ?? null) === 1;
            }),
            'Reporte'
        )
        ->once();

    $manager = new PreviewWindowReportManager($reports, $opener);

    $response = $manager->process(
        report: $report,
        request: $request,
        title: 'Reporte',
        route: 'exports.sales.stream',
    );

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getStatusCode())->toBe(204);
});

it('returns the PDF response when preview is requested', function () {
    /** @var LaravelReports|MockInterface $reports */
    $reports = Mockery::mock(LaravelReports::class);
    /** @var PreviewWindowOpener|MockInterface $opener */
    $opener = Mockery::mock(PreviewWindowOpener::class);

    $report = new class implements ReportDefinition {
        public function query(Request $request)
        {
            return collect([1]);
        }

        public function view(): string
        {
            return 'x';
        }

        public function filename(): string
        {
            return 'x.pdf';
        }

        public function shouldValidateLimit(): bool
        {
            return true;
        }
    };

    $request = Request::create('/exports/sales', 'GET', ['preview' => 1]);

    $pdfResponse = new Response('pdf');

    $reports->shouldReceive('process')
        ->with($report, $request, 'Reporte')
        ->once()
        ->andReturn($pdfResponse);

    $reports->shouldNotReceive('validateQuery');
    $opener->shouldNotReceive('openPdfWindow');

    $manager = new PreviewWindowReportManager($reports, $opener);

    $response = $manager->process(
        report: $report,
        request: $request,
        title: 'Reporte',
        route: 'exports.sales.stream',
    );

    expect($response)->toBe($pdfResponse);
});
