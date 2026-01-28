<?php

namespace Deifhelt\LaravelReports\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Deifhelt\LaravelReports\LaravelReports
 */
class LaravelReports extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Deifhelt\LaravelReports\LaravelReports::class;
    }
}
