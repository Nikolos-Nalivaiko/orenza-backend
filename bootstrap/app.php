<?php

declare(strict_types=1);

use App\Http\Middleware\ForceJsonResponse;
use App\Support\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Framework exceptions are translated into the same envelope that
        // App\Exceptions\DomainException produces.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error(__('messages.auth.unauthenticated'), Response::HTTP_UNAUTHORIZED, 'unauthenticated')
                : null;
        });

        // AuthorizationException is converted to AccessDeniedHttpException
        // before render callbacks run, so both are covered here.
        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error(
                    $e->getMessage() !== '' ? $e->getMessage() : __('messages.auth.forbidden'),
                    Response::HTTP_FORBIDDEN,
                    'forbidden',
                )
                : null;
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error(__('messages.http.not_found'), Response::HTTP_NOT_FOUND, 'not_found')
                : null;
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error(__('messages.http.not_found'), Response::HTTP_NOT_FOUND, 'not_found')
                : null;
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            return $request->is('api/*')
                ? ApiResponse::error(__('messages.http.too_many_requests'), Response::HTTP_TOO_MANY_REQUESTS, 'too_many_requests')
                : null;
        });
    })->create();
