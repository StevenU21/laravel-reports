<?php

use Deifhelt\LaravelReports\Facades\LaravelReports as LaravelReportsFacade;
use Deifhelt\LaravelReports\LaravelReports;

it('resolves the LaravelReports facade root', function () {
    expect(LaravelReportsFacade::getFacadeRoot())
        ->toBeInstanceOf(LaravelReports::class);
});
