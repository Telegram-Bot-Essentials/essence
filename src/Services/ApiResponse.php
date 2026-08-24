<?php

declare(strict_types=1);

namespace TelegramBotEssentials\Essence\Services;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The JSON envelope essence's HTTP endpoints reply with.
 *
 * Internalised from elyar/personal-laravel-helpers so essence does not
 * depend on it. Reached through tbeApiResponse() rather than the global
 * tbeApiResponse(), because apps that still install the original package
 * would otherwise have two definitions of that function and whichever
 * composer autoloaded first would win.
 */
class ApiResponse
{
    public function success(mixed $data = null, int $code = 200, array $extra = [], ?string $message = null): JsonResponse
    {
        return response()->json(array_merge([
            'success' => true,
            'message' => $message,
            'data' => $data ?? [],
        ], $extra), $code);
    }

    public function error(string $error, int $code = 400, ?string $errorType = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $error,
            'error_type' => $errorType ?? $this->describeStatus($code),
        ], $code);
    }

    public function errors(array $errors, int $code = 400, ?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Snake-cased status text for a code, e.g. 404 => "not_found".
     */
    private function describeStatus(int $code): string
    {
        $text = Response::$statusTexts[$code] ?? 'unknown_error';

        return strtolower(str_replace(' ', '_', $text));
    }
}
