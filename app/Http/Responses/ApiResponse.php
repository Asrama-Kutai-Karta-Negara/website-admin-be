<?php

namespace App\Http\Responses;

abstract class ApiResponse
{
    public static function success($message = 'Operation successful', $data = null, $status = 200)
    {
        if (is_null($message)) {
            $message = 'Operation successful';
        }
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    public static function pagination($message = 'Operation successful', $data = null, $status = 200)
    {
        if (is_null($message)) {
            $message = 'Operation successful';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'count' => $data->total(),
            'current_page' => $data->currentPage(),
            'previous_paage' => $data->currentPage() - 1,
            'total_pages' => $data->lastPage(),
            'data' => $data->items()
        ], $status);
    }

    public static function error($message = 'Something went wrong', $status = 400)
    {
        if (is_null($message)) {
            $message = 'Something went wrong';
        }
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null
        ], $status);
    }
}
