<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SteadfastService
{
    private $baseUrl = 'https://portal.packzy.com/api/v1';
    private $apiKey;
    private $secretKey;

    public function __construct()
    {
        $this->apiKey = env('STEADFAST_API_KEY', 'bzt8irxfy4bmkaulovt4lzzyldjxsngm');
        $this->secretKey = env('STEADFAST_SECRET_KEY', '94trueu6ukye49pdakdq8i8v');
    }

    /**
     * Create order in Steadfast
     * 
     * @param array $orderData
     * @return array ['success' => bool, 'data' => array|null, 'error' => string|null]
     */
    public function createOrder(array $orderData): array
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Secret-Key' => $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/create_order', $orderData);

            $responseData = $response->json();

            if (isset($responseData['errors']) && !empty($responseData['errors'])) {
                $errorMessage = $responseData['message'] ?? 'Steadfast API validation failed';
                if (is_array($responseData['errors'])) {
                    $errorDetails = [];
                    foreach ($responseData['errors'] as $field => $messages) {
                        if (is_array($messages)) {
                            $errorDetails[$field] = implode(', ', $messages);
                        } else {
                            $errorDetails[$field] = $messages;
                        }
                    }
                    $errorMessage .= ': ' . json_encode($errorDetails);
                } else {
                    $errorMessage .= ': ' . json_encode($responseData['errors']);
                }

                Log::error('STEADFAST_CREATE_ORDER_VALIDATION_FAILED', [
                    'request' => $orderData,
                    'response' => $responseData,
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'data' => null,
                    'error' => $errorMessage,
                    'status_code' => $response->status(),
                ];
            }

            if ($response->successful() && isset($responseData['status']) && $responseData['status'] == 200) {
                if (isset($responseData['consignment'])) {
                    return [
                        'success' => true,
                        'data' => $responseData['consignment'],
                        'error' => null,
                    ];
                }
            }

            $errorMessage = $responseData['message'] ?? 'Steadfast API request failed';
            if (isset($responseData['errors'])) {
                if (is_array($responseData['errors'])) {
                    $errorDetails = [];
                    foreach ($responseData['errors'] as $field => $messages) {
                        if (is_array($messages)) {
                            $errorDetails[$field] = implode(', ', $messages);
                        } else {
                            $errorDetails[$field] = $messages;
                        }
                    }
                    $errorMessage .= ': ' . json_encode($errorDetails);
                } else {
                    $errorMessage .= ': ' . json_encode($responseData['errors']);
                }
            }

            Log::error('STEADFAST_CREATE_ORDER_FAILED', [
                'request' => $orderData,
                'response' => $responseData,
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => $errorMessage,
                'status_code' => $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('STEADFAST_CREATE_ORDER_EXCEPTION', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'data' => null,
                'error' => 'Steadfast API exception: ' . $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }

    /**
     * Get delivery status by consignment ID
     * 
     * @param string $consignmentId
     * @return array|null
     */
    public function getStatusByConsignmentId(string $consignmentId): ?array
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Secret-Key' => $this->secretKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/status_by_cid/' . $consignmentId);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('STEADFAST_GET_STATUS_FAILED', [
                'consignment_id' => $consignmentId,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get delivery status by invoice
     * 
     * @param string $invoice
     * @return array|null
     */
    public function getStatusByInvoice(string $invoice): ?array
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Secret-Key' => $this->secretKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/status_by_invoice/' . $invoice);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('STEADFAST_GET_STATUS_BY_INVOICE_FAILED', [
                'invoice' => $invoice,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get delivery status by tracking code
     * 
     * @param string $trackingCode
     * @return array|null
     */
    public function getStatusByTrackingCode(string $trackingCode): ?array
    {
        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Secret-Key' => $this->secretKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/status_by_trackingcode/' . $trackingCode);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('STEADFAST_GET_STATUS_BY_TRACKING_FAILED', [
                'tracking_code' => $trackingCode,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

