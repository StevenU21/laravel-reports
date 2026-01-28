<?php

namespace Deifhelt\LaravelReports\Traits;

use Illuminate\Support\Collection;

trait DefaultReportConfiguration
{
    public function shouldValidateLimit(): bool
    {
        return false;
    }

    public function paper(): string|array
    {
        return 'letter';
    }

    public function orientation(): string
    {
        return 'portrait';
    }

    public function viewData(Collection $data): array
    {
        return [
            'data' => $data,
        ];
    }

    public function summary(Collection $data): array
    {
        return [];
    }
}
