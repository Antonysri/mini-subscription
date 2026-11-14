<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * SUCCESS RESPONSE ENVELOPE:
     * Request successfully mudinja intha method-ah call pannuvom.
     * 
     * @param mixed $data         - Client-ku anupa vendiya data (User object, Token, Plans array, etc.)
     * @param string $message     - Oru puriyura mathiri success message (eg: 'User registered successfully')
     * @param int $statusCode     - HTTP Status Code (Default: 200 OK, or 201 Created)
     * 
     * Example Output:
     * {
     *     "success": true,
     *     "message": "User registered successfully",
     *     "data": { "id": 1, "name": "Antony" }
     * }
     */
    public function successResponse(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    /**
     * ERROR RESPONSE ENVELOPE:
     * Ethavathu error vantha (Validation fail, Wrong password, Not found) itha call pannuvom.
     * 
     * @param string $message     - Error-ah describe panra message (eg: 'Invalid credentials')
     * @param int $statusCode     - HTTP Error Status Code (400, 401 Unauthorized, 404, 422, etc.)
     * @param mixed $errors       - Field-level validation errors (optional)
     * 
     * Example Output:
     * {
     *     "success": false,
     *     "message": "Invalid email or password",
     *     "errors": null
     * }
     */
    public function errorResponse(
        string $message = 'Error',
        int $statusCode = 400,
        mixed $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        // Errors array iruntha mattum JSON-kulla serpikkum
        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}