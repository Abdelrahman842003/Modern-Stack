<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Generate a standardized API response.
     *
     * @param  int  $code  HTTP status code
     * @param  string|null  $message  Response message
     * @param  mixed|null  $errors  Error details (array or null)
     * @param  mixed|null  $data  Response data
     * @param  array|null  $meta  Additional metadata (pagination, timing, etc.)
     */
    public function apiResponse(
        int $code = 200,
        ?string $message = null,
        $errors = null,
        $data = null,
        ?array $meta = null
    ): JsonResponse {
        // Initialize the response array
        $response = [
            'status' => $code,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];

        // Add errors if they exist
        if (! is_null($errors)) {
            $response['errors'] = $errors;
        }

        // Add data if it exists
        if (! is_null($data)) {
            $response['data'] = $data;
        }

        // Add metadata if exists (pagination, request_id, etc.)
        if (! is_null($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    /**
     * Generate a success response.
     *
     * @param  mixed  $data
     */
    public function successResponse(
        $data = null,
        string $message = 'Success',
        int $code = 200,
        ?array $meta = null
    ): JsonResponse {
        return $this->apiResponse($code, $message, null, $data, $meta);
    }

    /**
     * Generate an error response.
     *
     * @param  mixed  $errors
     */
    public function errorResponse(
        string $message = 'Error',
        $errors = null,
        int $code = 400
    ): JsonResponse {
        return $this->apiResponse($code, $message, $errors);
    }
}
