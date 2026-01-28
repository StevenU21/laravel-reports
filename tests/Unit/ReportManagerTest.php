<?php

use Barryvdh\DomPDF\Facade\Pdf;
use Deifhelt\LaravelReports\Exceptions\ReportException;
use Deifhelt\LaravelReports\Interfaces\ReportDefinition;
use Deifhelt\LaravelReports\LaravelReports;
use Deifhelt\LaravelReports\Traits\DefaultReportConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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

beforeEach(function () {
    $mockResponse = Mockery::mock(\Illuminate\Http\Response::class);
    $mockResponse->shouldReceive('getContent')->andReturn('downloaded');

    $mockStreamResponse = Mockery::mock(\Illuminate\Http\Response::class);
    $mockStreamResponse->shouldReceive('getContent')->andReturn('streamed');

    Pdf::shouldReceive('loadView')->andReturnSelf()->byDefault();
    Pdf::shouldReceive('setPaper')->andReturnSelf()->byDefault();
    Pdf::shouldReceive('stream')->andReturn($mockStreamResponse)->byDefault();
    Pdf::shouldReceive('download')->andReturn($mockResponse)->byDefault();
});

it('can download a report by default', function () {
    $manager = new LaravelReports;
    $report = new TestReport;
    $request = Request::create('/report', 'GET');

    Pdf::shouldReceive('loadView')
        ->with('test-view', \Mockery::on(function ($data) {
            return isset($data['data']) && count($data['data']) === 2;
        }))
        ->once()
        ->andReturnSelf();

    Pdf::shouldReceive('download')
        ->with('test.pdf')
        ->once()
        ->andReturn(Mockery::mock(\Illuminate\Http\Response::class, function ($mock) {
            $mock->shouldReceive('getContent')->andReturn('downloaded');
        }));

    $response = $manager->process($report, $request);

    expect($response->getContent())->toBe('downloaded');
});

it('can stream a report when requested', function () {
    $manager = new LaravelReports;
    $report = new TestReport;
    $request = Request::create('/report', 'GET', ['preview' => true]);

    Pdf::shouldReceive('stream')
        ->with('test.pdf')
        ->once()
        ->andReturn(Mockery::mock(\Illuminate\Http\Response::class, function ($mock) {
            $mock->shouldReceive('getContent')->andReturn('streamed');
        }));

    $response = $manager->process($report, $request);

    expect($response->getContent())->toBe('streamed');
});

it('validates empty data throws exception', function () {
    $manager = new LaravelReports;
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

it('validates max records throws exception', function () {
    $manager = new LaravelReports;
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

it('passes validation with correct data count', function () {
    $manager = new LaravelReports;
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
    Pdf::shouldReceive('download');
    $manager->process($report, $request);

    expect(true)->toBeTrue();
});

it('merges view data and configuration correctly', function () {
    $manager = new LaravelReports;
    $report = new TestReportWithExtras;
    $request = Request::create('/report', 'GET');

    Pdf::shouldReceive('loadView')
        ->with('test-view', \Mockery::on(function ($data) {
            return $data['extra'] === 'value'
                && $data['summary']['total'] === 2
                && $data['totals']['total'] === 2;
        }))
        ->once()
        ->andReturnSelf();

    Pdf::shouldReceive('setPaper')
        ->with('a4', 'landscape')
        ->once()
        ->andReturnSelf();

    $manager->process($report, $request);
});

it('respects configured max records limit', function () {
    config(['reports.limit' => 50]);

    $manager = new LaravelReports;
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

it('allows increasing max records limit', function () {
    config(['reports.limit' => 2000]);

    $manager = new LaravelReports;
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
    Pdf::shouldReceive('download');
    $manager->process($report, $request);

    expect(true)->toBeTrue();
});
