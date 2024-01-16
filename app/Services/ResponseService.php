<?php

namespace App\Services;


class ResponseService
{
    public function validationErrorResponse($errors) {

        return response()->json([
            'message' => 'Validation error',
            'errors' => $errors,
        ], 422);
    }

    public function successResponse($message, $data = null) {

        $response = [
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, 200);
    }

    public function errorResponse($message, $statusCode) {
        return response()->json([
            'message' => $message,
        ], $statusCode);
    }
}