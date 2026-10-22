<?php

declare(strict_types=1);

namespace App\Http\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

trait Paginatable
{
    /**
     * Page size from ?per_page: missing, non-numeric or < 1 -> $default; capped at $max.
     */
    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        $perPage = $request->integer('per_page', $default);

        return $perPage < 1 ? $default : min($perPage, $max);
    }

    /**
     * Format paginated data with pagination meta.
     *
     * @param  string  $key  The key name for the data array (e.g., 'employers', 'employees')
     */
    protected function paginatedResponse(LengthAwarePaginator $paginator, string $key): array
    {
        return [
            $key => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }
}
