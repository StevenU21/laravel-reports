<?php

use Deifhelt\LaravelReports\Exceptions\ReportException;
use Deifhelt\LaravelReports\Interfaces\PdfRenderer;
use Deifhelt\LaravelReports\Interfaces\ReportDefinition;
use Deifhelt\LaravelReports\LaravelReports;
use Deifhelt\LaravelReports\Traits\DefaultReportConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Helper class for testing
 */
class TestReport implements ReportDefinition
{
    use DefaultReportConfiguration;

    public $data = ['item1', 'item2'];

    public $shouldValidate = false;

    public function query(Request $request)
    {
        return collect($this->data);
    }

    public function view(): string
    {
        return 'test-view';
    }

    public function filename(): string
    {
        return 'test.pdf';
    }

    public function shouldValidateLimit(): bool
    {
        return $this->shouldValidate;
    }
}

class TestReportWithExtras extends TestReport
{
    public function viewData(Collection $data): array
    {
        return ['extra' => 'value'];
    }

    public function summary(Collection $data): array
    {
        return ['total' => $data->count()];
    }

    public function paper(): string|array
    {
        return 'a4';
    }

    public function orientation(): string
    {
        return 'landscape';
    }
}

$pdfRenderer = null;

beforeEach(function () use (&$pdfRenderer) {
    $pdfRenderer = Mockery::mock(PdfRenderer::class);

    $pdfRenderer->shouldReceive('download')->andReturn(new Response('downloaded'))->byDefault();
    $pdfRenderer->shouldReceive('stream')->andReturn(new Response('streamed'))->byDefault();
});

it('can download a report by default', function () use (&$pdfRenderer) {
    $manager = new LaravelReports($pdfRenderer);
    $report = new TestReport;
    $request = Request::create('/report', 'GET');

    $pdfRenderer->shouldReceive('download')
        ->with(
            'test-view',
            Mockery::on(function (array $data) {
                return isset($data['data']) && count($data['data']) === 2;
            }),
            Mockery::any(),
            Mockery::any(),
            'test.pdf'
        )
        ->once()
        ->andReturn(new Response('downloaded'));

    $response = $manager->process($report, $request);

    expect($response->getContent())->toBe('downloaded');
});

it('can stream a report when requested', function () use (&$pdfRenderer) {
    $manager = new LaravelReports($pdfRenderer);
    $report = new TestReport;
    $request = Request::create('/report', 'GET', ['preview' => true]);

    $pdfRenderer->shouldReceive('stream')
        ->with('test-view', Mockery::any(), Mockery::any(), Mockery::any(), 'test.pdf')
        ->once()
        ->andReturn(new Response('streamed'));

    $response = $manager->process($report, $request);

    expect($response->getContent())->toBe('streamed');
});

it('validates empty data throws exception', function () use (&$pdfRenderer) {
    $manager = new LaravelReports($pdfRenderer);
    $report = new class extends TestReport
    {
        public $shouldValidate = true;

        public function query(Request $request)
        {
            return collect([]);
        }
    };
    $request = Request::create('/report', 'GET');

    $manager->process($report, $request);

})->throws(ReportException::class, 'No data available');

it('validates max records throws exception', function () use (&$pdfRenderer) {
    $manager = new LaravelReports($pdfRenderer);
    $report = new class extends TestReport
    {
        public $shouldValidate = true;

        public function query(Request $request)
        {
            // Return more than 1000 items
            return collect(range(1, 1001));
        }
    };
    $request = Request::create('/report', 'GET');

    $manager->process($report, $request);

})->throws(ReportException::class, 'exceeds the allowed limit');

it('passes validation with correct data count', function () use (&$pdfRenderer) {
    $manager = new LaravelReports($pdfRenderer);
    $report = new class extends TestReport
    {
        public $shouldValidate = true;

        public function query(Request $request)
        {
            return collect(range(1, 10));
        }
    };
    $request = Request::create('/report', 'GET');

    // Should not throw
    $pdfRenderer->shouldReceive('download');
    $manager->process($report, $request);

    expect(true)->toBeTrue();
});

it('merges view data and configuration correctly', function () use (&$pdfRenderer) {
    $manager = new LaravelReports($pdfRenderer);
    $report = new TestReportWithExtras;
    $request = Request::create('/report', 'GET');

    $pdfRenderer->shouldReceive('download')
        ->with(
            'test-view',
            Mockery::on(function (array $data) {
                return $data['extra'] === 'value'
                    && $data['summary']['total'] === 2
                    && $data['totals']['total'] === 2;
            }),
            'a4',
            'landscape',
            'test.pdf'
        )
        ->once();

    $manager->process($report, $request);
});

it('respects configured max records limit', function () use (&$pdfRenderer) {
    config(['reports.limit' => 50]);

    $manager = new LaravelReports($pdfRenderer);
    $report = new class extends TestReport
    {
        public $shouldValidate = true;

        public function query(Request $request)
        {
            // Return 51 items (more than configured 50)
            return collect(range(1, 51));
        }
    };
    $request = Request::create('/report', 'GET');

    $manager->process($report, $request);

})->throws(ReportException::class, 'exceeds the allowed limit of 50 records');

it('allows increasing max records limit', function () use (&$pdfRenderer) {
    config(['reports.limit' => 2000]);

    $manager = new LaravelReports($pdfRenderer);
    $report = new class extends TestReport
    {
        public $shouldValidate = true;

        public function query(Request $request)
        {
            // Return 1500 items (default is 1000, but config is 2000)
            return collect(range(1, 1500));
        }
    };
    $request = Request::create('/report', 'GET');

    // Should not throw
    $pdfRenderer->shouldReceive('download');
    $manager->process($report, $request);

    expect(true)->toBeTrue();
});
