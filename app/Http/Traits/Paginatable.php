<?php

declare(strict_types=1);

namespace App\Http\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

trait Paginatable
{
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
