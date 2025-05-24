<?php

declare(strict_types=1);

namespace App\Bootstrap;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WithExceptions
{
    public function __invoke(Exceptions $exceptions): void
    {
        $this->render($exceptions);
    }

    private function render(Exceptions $exceptions): void
    {
        $exceptions->render(static function (ValidationException $e, Request $request) {
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
        $exceptions->render(static function (NotFoundHttpException $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Not found',
                ], 404);
            }
        });
        $exceptions->render(static function (HttpExceptionInterface $e, Request $request) {
            if ($request->wantsJson() || $request->is('api/*')) {
                logger()->error($e->getMessage(), $e->getTrace());

                return response()->json(['message' => 'Some error'], status: $e->getStatusCode());
            }
        });
    }
}
