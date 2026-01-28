<?php

namespace Deifhelt\LaravelReports\Interfaces;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

interface ReportDefinition
{
    /**
     * Returns the query builder or data collection source.
     *
     * @return Builder|Collection|mixed
     */
    public function query(Request $request);

    /**
     * The Blade view to render.
     */
    public function view(): string;

    /**
     * The filename for the download.
     */
    public function filename(): string;

    /**
     * Whether to validate record limits.
     */
    public function shouldValidateLimit(): bool;

    /**
     * Paper configuration (e.g., 'letter', 'a4', or array [0,0,w,h]).
     * Optional by default via Trait or Manager check.
     *
     * @return string|array
     */
    // public function paper(): string|array;

    /**
     * Paper orientation ('portrait' or 'landscape').
     * Optional by default via Trait or Manager check.
     */
    // public function orientation(): string;

    /**
     * Data to be passed to the view.
     *
     * @param  Collection  $data
     * @return array
     */
    // public function viewData(Collection $data): array;
}
