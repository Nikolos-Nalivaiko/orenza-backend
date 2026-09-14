<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TrackObjectResource;
use App\Repositories\Contracts\ObjectRepositoryInterface;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class TrackController extends Controller
{
    public function __construct(
        private readonly ObjectRepositoryInterface $objects,
    ) {}

    public function __invoke(string $token): JsonResponse
    {
        $object = $this->objects->findByPublicToken($token);

        if ($object === null) {
            return ApiResponse::error(__('messages.track.not_found'), Response::HTTP_NOT_FOUND, 'not_found');
        }

        return ApiResponse::success((new TrackObjectResource($object))->resolve());
    }
}
