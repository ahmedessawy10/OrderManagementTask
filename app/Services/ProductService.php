<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Traits\Paginatable;
use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class ProductService
{
    use Paginatable;

    /**
     * Get paginated products using QueryBuilder with filters, sorts, and pagination.
     */
    public function getProducts(?int $perPage = null, ?Request $request = null): LengthAwarePaginator
    {
        $request = $request ?? request();
        $perPage = $perPage ?? $this->perPage($request);

        return $this->buildQuery($request)
            ->paginate($perPage)
            ->appends($request->query());
    }

    /**
     * Build the Spatie QueryBuilder instance for products.
     */
    public function buildQuery(?Request $request = null): QueryBuilder
    {
        $request = $request ?? request();

        return QueryBuilder::for(Product::class, $request)
            ->with(['translations', 'media'])
            ->allowedFilters(
                AllowedFilter::exact('id'),
                AllowedFilter::exact('price'),
                AllowedFilter::callback('name', function (Builder $query, mixed $value): void {
                    $query->whereHas('translations', function (Builder $q) use ($value): void {
                        $q->where('name', 'like', "%{$value}%");
                    });
                }),
                AllowedFilter::callback('search', function (Builder $query, mixed $value): void {
                    $query->where(function (Builder $builder) use ($value): void {
                        $builder->whereHas('translations', function (Builder $q) use ($value): void {
                            $q->where('name', 'like', "%{$value}%")
                                ->orWhere('description', 'like', "%{$value}%");
                        });
                    });
                }),
                AllowedFilter::callback('min_price', function (Builder $query, mixed $value): void {
                    $query->where('price', '>=', (float) $value);
                }),
                AllowedFilter::callback('max_price', function (Builder $query, mixed $value): void {
                    $query->where('price', '<=', (float) $value);
                }),
                AllowedFilter::callback('locale', function (Builder $query, mixed $value): void {
                    $query->whereHas('translations', function (Builder $q) use ($value): void {
                        $q->where('locale', (string) $value);
                    });
                }),
            )
            ->allowedSorts(
                AllowedSort::field('id'),
                AllowedSort::field('price'),
                AllowedSort::field('created_at'),
                AllowedSort::callback('name', function (Builder $query, bool $descending): void {
                    $query->orderBy(
                        ProductTranslation::query()
                            ->select('name')
                            ->whereColumn('product_translations.product_id', 'products.id')
                            ->where('locale', app()->getLocale())
                            ->limit(1),
                        $descending ? 'desc' : 'asc'
                    );
                }),
            )
            ->allowedIncludes(
                'translations',
                'media',
            )
            ->defaultSort('-created_at');
    }
}
