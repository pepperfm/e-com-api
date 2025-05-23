<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(static function (\Illuminate\Validation\ValidationException $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                $errors = $e->errors();
                foreach ($errors as &$error) {
                    $error = $error[0];
                }

                return response()->json([
                    'errors' => $errors,
                    'message' => $e->getMessage(),
                ], 422);
            }
        });
        $exceptions->render(static function (HttpExceptionInterface $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                logger()->error($e->getMessage(), $e->getTrace());

                return response()->json(['message' => 'Some error'], status: $e->getStatusCode());
            }
        });
    })->create();
