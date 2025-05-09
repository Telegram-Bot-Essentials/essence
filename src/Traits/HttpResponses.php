<?php

namespace Elyar\TelegramBotEssentials\Traits;

use Illuminate\Http\JsonResponse;

trait HttpResponses
{
    protected function success(mixed $data = null, int $code = 200, array $extra = [], string $message = null): JsonResponse
    {
        $response = [
            'success' => true
        ];

        empty($data) ? $response['data'] = [] : $response['data'] = $data;
        if(isset($extra)) $response = array_merge($response, $extra);
        empty($message) ?: $response['message'] = $message;

        return response()->json($response, $code);
    }

    protected function error(string $error, int $code = 400, ?string $errorType = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error
        ];

        empty($errorType) ?: $response['error_type'] = $errorType;

        return response()->json($response, $code);
    }

    public function errors(array $errors, int $code = 400, string $message = null): JsonResponse
    {
        $response = [
            'success' => false,
            'errors' => $errors
        ];

        empty($message) ?: $response['message'] = $message;


        return response()->json($response, $code);
    }
}
