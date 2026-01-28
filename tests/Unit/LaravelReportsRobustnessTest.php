<?php

use Barryvdh\DomPDF\Facade\Pdf;
use Deifhelt\LaravelReports\Interfaces\ReportDefinition;
use Deifhelt\LaravelReports\LaravelReports;
use Deifhelt\LaravelReports\Traits\DefaultReportConfiguration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class LrTenant extends Model
{
    protected $table = 'lr_tenants';
    public $timestamps = false;
    protected $guarded = [];
}

class LrPurchase extends Model
{
    protected $table = 'lr_purchases';
    public $timestamps = false;
    protected $guarded = [];

    public function tenant()
    {
        return $this->belongsTo(LrTenant::class, 'tenant_id');
    }
}

beforeEach(function () {
    Schema::dropAllTables();

    Schema::create('lr_tenants', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
    });

    Schema::create('lr_purchases', function (Blueprint $table) {
        $table->increments('id');
        $table->unsignedInteger('tenant_id');
        $table->string('status');
    });

    $tenantA = LrTenant::query()->create(['name' => 'A']);
    $tenantB = LrTenant::query()->create(['name' => 'B']);

    LrPurchase::query()->create(['tenant_id' => $tenantA->id, 'status' => 'paid']);
    LrPurchase::query()->create(['tenant_id' => $tenantA->id, 'status' => 'pending']);
    LrPurchase::query()->create(['tenant_id' => $tenantB->id, 'status' => 'paid']);

    $mockDownloadResponse = Mockery::mock(\Illuminate\Http\Response::class);
    $mockDownloadResponse->shouldReceive('getContent')->andReturn('downloaded');

    $mockStreamResponse = Mockery::mock(\Illuminate\Http\Response::class);
    $mockStreamResponse->shouldReceive('getContent')->andReturn('streamed');

    Pdf::shouldReceive('loadView')->andReturnSelf()->byDefault();
    Pdf::shouldReceive('setPaper')->andReturnSelf()->byDefault();
    Pdf::shouldReceive('stream')->andReturn($mockStreamResponse)->byDefault();
    Pdf::shouldReceive('download')->andReturn($mockDownloadResponse)->byDefault();
});

it('evaluates query only once during process', function () {
    $manager = new LaravelReports;

    $calls = 0;
    $report = new class($calls) implements ReportDefinition {
        use DefaultReportConfiguration;

        public function __construct(private int &$calls) {}

        public function query(Request $request)
        {
            $this->calls++;

            return collect([1, 2]);
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
            return true;
        }
    };

    Pdf::shouldReceive('loadView')->once()->andReturnSelf();

    $manager->process($report, Request::create('/report', 'GET'));

    expect($calls)->toBe(1);
});

it('supports eloquent builder with when filters', function () {
    $manager = new LaravelReports;

    $report = new class implements ReportDefinition {
        use DefaultReportConfiguration;

        public function query(Request $request)
        {
            return LrPurchase::query()
                ->when($request->has('status'), fn ($q) => $q->where('status', $request->get('status')));
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
            return true;
        }
    };

    Pdf::shouldReceive('loadView')
        ->with('test-view', Mockery::on(function (array $data) {
            return $data['data'] instanceof Collection
                && $data['data']->count() === 2
                && ($data['filters']['status'] ?? null) === 'paid';
        }))
        ->once()
        ->andReturnSelf();

    $response = $manager->process($report, Request::create('/report', 'GET', ['status' => 'paid']));
    expect($response->getContent())->toBe('downloaded');
});

it('supports relation queries', function () {
    $manager = new LaravelReports;

    $tenantA = LrTenant::query()->where('name', 'A')->firstOrFail();

    $report = new class($tenantA) implements ReportDefinition {
        use DefaultReportConfiguration;

        public function __construct(private LrTenant $tenant) {}

        public function query(Request $request)
        {
            return $this->tenant->hasMany(LrPurchase::class, 'tenant_id');
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
            return true;
        }
    };

    Pdf::shouldReceive('loadView')
        ->with('test-view', Mockery::on(function (array $data) {
            return $data['data'] instanceof Collection && $data['data']->count() === 2;
        }))
        ->once()
        ->andReturnSelf();

    $manager->process($report, Request::create('/report', 'GET'));
});

it('only exposes query-string parameters as filters to the view', function () {
    $manager = new LaravelReports;

    $report = new class implements ReportDefinition {
        use DefaultReportConfiguration;

        public function query(Request $request)
        {
            return collect([1]);
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
            return false;
        }
    };

    Pdf::shouldReceive('loadView')
        ->with('test-view', Mockery::on(function (array $data) {
            return ($data['filters']['status'] ?? null) === 'paid'
                && ! array_key_exists('secret', $data['filters']);
        }))
        ->once()
        ->andReturnSelf();

    // 'secret' is in body (POST), not in query-string
    $request = Request::create('/report?status=paid', 'POST', ['secret' => 'do-not-leak']);
    $manager->process($report, $request);
});
