<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;

/**
 * Single place where the shape of every API response is decided.
 *
 * Success: { "data": ..., "message": ..., "meta": ... }
 * Failure: { "message": ..., "error_code": ..., "errors": ... }
 */
final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $status = Response::HTTP_OK,
        array $meta = [],
    ): JsonResponse {
        $payload = ['data' => $data];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function created(mixed $data = null, ?string $message = null, array $meta = []): JsonResponse
    {
        return self::success($data, $message, Response::HTTP_CREATED, $meta);
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public static function error(
        string $message,
        int $status = Response::HTTP_BAD_REQUEST,
        string $errorCode = 'error',
        array $errors = [],
    ): JsonResponse {
        $payload = [
            'message' => $message,
            'error_code' => $errorCode,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * Wrap a paginator into the standard envelope, keeping pagination metadata.
     *
     * @param  LengthAwarePaginator<int, \Illuminate\Database\Eloquent\Model>  $paginator
     * @param  class-string<JsonResource>  $resource
     */
    public static function paginated(LengthAwarePaginator $paginator, string $resource, ?string $message = null): JsonResponse
    {
        /** @var ResourceCollection $collection */
        $collection = $resource::collection($paginator);

        return self::success(
            data: $collection->resolve(),
            message: $message,
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        );
    }
}
