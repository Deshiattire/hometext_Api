<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

class AppHelper{

    public static function DataPaginate($items, $perPage)
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

    public static function SteadfastOrder($data){
        $response = Http::withHeaders([
            'Api-Key' => env('STEADFAST_API_KEY'),
            'Secret-Key' => env('STEADFAST_SECRET_KEY'),
            'Content-Type' => 'application/json'

        ])->post('https://portal.packzy.com/api/v1/create_order/bulk-order', [
            'data' => $data,
        ]);

        return json_decode($response->getBody()->getContents());
    }

    public static function SteadfastOrderTracking($trackingNumber){
        $response = Http::withHeaders([
            'Api-Key' => env('STEADFAST_API_KEY'),
            'Secret-Key' => env('STEADFAST_SECRET_KEY')

        ])->get('https://portal.packzy.com/api/v1/status_by_trackingcode/'.$trackingNumber);

        return json_decode($response->getBody()->getContents());
    }
}
