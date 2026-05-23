<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command('retreat:monitor-activity-attendance-deadlines')->everyFiveMinutes();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request): ?JsonResponse {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'message' => 'Trop de tentatives en peu de temps. Patientez quelques instants puis réessayez.',
            ], 429);
        });

        $exceptions->render(function (ValidationException $e, Request $request): ?JsonResponse {
            if (! $request->expectsJson()) {
                return null;
            }

            $errors = $e->errors();
            $first = collect($errors)->flatten()->first();
            $safe = is_string($first) ? $first : 'Certaines informations sont invalides. Vérifiez le formulaire.';

            if (str_starts_with($safe, 'validation.')) {
                $safe = 'Certaines informations sont invalides. Vérifiez le formulaire puis réessayez.';
            }

            return response()->json([
                'message' => $safe,
                'errors' => $errors,
            ], 422);
        });
    })->create();
