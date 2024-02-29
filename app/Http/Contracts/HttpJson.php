<?php

namespace App\Http\Contracts;

class HttpJson {
    public static function OK(mixed $data, int $status = 200) {
        return response()->json([
            'data' => $data
        ], $status);
    }

    public static function KO(string $message, int $status = 500, array $extra = []) {
        return response()->json([
            'error' => [
                'message' => $message,
                ...$extra
            ]
        ], $status);
    }
}
