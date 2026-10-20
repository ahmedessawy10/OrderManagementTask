<?php

declare(strict_types=1);

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return a standardized API response.
     *
     * @param  string  $message  Response message
     * @param  mixed  $data  Response data (will be 'data' on success, 'errors' on failure)
     * @param  int  $code  HTTP status code
     * @param  array<string, mixed>  $meta  Additional metadata
     */
    protected function responseApi(
        string $message,
        mixed $data = null,
        int $code = 200,
        array $meta = []
    ): JsonResponse {
        $status = $code >= 200 && $code < 300;

        $response = [
            'status' => $status,
            'message' => $message,
        ];

        if ($data !== null) {
            $response[$status ? 'data' : 'errors'] = $data;
        }

        if (! empty($meta)) {
            $response = array_merge($response, $meta);
        }

        return response()->json($response, $code);
    }

    /**
     * @param  bool  $preserveKeys  يمنع إعادة ترقيم الـ array عشان ترجع object بمفاتيحها.
     *                              الافتراضي false للحفاظ على سلوك كل الـ endpoints القديمة.
     */
    protected function success(string $message = 'Success', mixed $data = null, int $status = 200, array $meta = [], bool $preserveKeys = false): JsonResponse
    {
        if (! $preserveKeys && is_array($data) && ! isset($data['id']) && ! empty($data)) {
            $data = array_values($data);
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
            ...$meta,
        ], $status);
    }

    protected function fail(string $message = 'Error', int $status = 400, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    protected function getPagination($paginator): array
    {
        return [
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
