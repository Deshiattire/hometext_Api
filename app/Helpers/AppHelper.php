<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AppHelper{
    
    public static function DataPaginate($items, $perPage = 10)
    {

        $currentPage = request()->get('page', 1); // Get current page from request
        $perPage = $perPage; // Number of items per page
        $offset = ($currentPage - 1) * $perPage;

        $paginatedData = new LengthAwarePaginator(
            $items->slice($offset, $perPage)->values(),
            $items->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return $paginatedData;
    }

    public static function ResponseFormat(bool $status, string $message, $data=null, $errorMessage=null)
    {
        return response()->json([
            'success' => $status,
            'message' => $message,
            'data' => $data,
            'error' => $errorMessage
        ], $status? 200: 400);
    }
}