<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Client\ProductResource;
use App\Http\Traits\ApiResponse;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Display a listing of products with filtering, sorting, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->getProducts(request: $request);

        return $this->success(
            __('messages.product.retrieved'),
            ProductResource::collection($products->getCollection()),
            200,
            $this->getPagination($products)
        );
    }
}
