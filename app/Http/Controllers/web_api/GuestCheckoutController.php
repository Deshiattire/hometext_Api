<?php

namespace App\Http\Controllers\web_api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuestCheckoutRequest;
use App\Manager\OrderManager;
use App\Manager\PriceManager;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\SteadfastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GuestCheckoutController extends Controller
{
    /**
     * Default shop ID for guest orders
     */
    private const DEFAULT_SHOP_ID = 4;
    
    /**
     * Default sales manager ID for guest orders
     */
    private const DEFAULT_SALES_MANAGER_ID = 2;

    /**
     * Process guest checkout
     * 
     * @param GuestCheckoutRequest $request
     * @return JsonResponse
     */
    public function checkout(GuestCheckoutRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Extract validated data
            $customerData = $request->input('customer');
            $items = $request->input('items');
            $shippingAddress = $request->input('shippingAddress');
            $billingAddress = $request->input('billingAddress', $shippingAddress);
            $paymentMethodData = $request->input('paymentMethod');
            $notes = $request->input('notes');
            $deliveryType = (int) $request->input('deliveryType', 0);

            // Validate and prepare cart items
            $cartValidation = $this->validateAndPrepareCartItems($items);
            if (!$cartValidation['success']) {
                DB::rollBack();
                return response()->json($cartValidation, $cartValidation['statusCode']);
            }

            $orderDetailsData = $cartValidation['orderDetails'];
            $subTotal = $cartValidation['subTotal'];
            $discount = $cartValidation['discount'];
            $total = $cartValidation['total'];
            $quantity = $cartValidation['quantity'];

            // Get or create guest customer record (optional - for tracking repeat guests)
            $customer = $this->findOrCreateGuestCustomer($customerData);

            // Get payment method
            $paymentMethod = $this->resolvePaymentMethod($paymentMethodData['type']);
            if (!$paymentMethod) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment method',
                ], 400);
            }

            // Generate order number and guest token
            $orderNumber = OrderManager::generateOrderNumber(self::DEFAULT_SHOP_ID);
            $guestToken = $this->generateGuestToken();

            // Prepare guest name
            $guestName = trim($customerData['firstName'] . ' ' . ($customerData['lastName'] ?? ''));
            $shippingName = trim($shippingAddress['firstName'] . ' ' . ($shippingAddress['lastName'] ?? ''));
            $billingName = isset($billingAddress['firstName']) 
                ? trim($billingAddress['firstName'] . ' ' . ($billingAddress['lastName'] ?? ''))
                : $shippingName;

            // Create order with Steadfast integration (if applicable)
            $steadfastResult = $this->createSteadfastOrder(
                $orderNumber,
                $shippingName,
                $shippingAddress['phone'],
                $shippingAddress,
                $total,
                $notes,
                $orderDetailsData,
                $quantity,
                $deliveryType,
                $shippingAddress['email'] ?? $customerData['email']
            );

            if (!$steadfastResult['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Order creation failed: Delivery service integration failed',
                    'error' => $steadfastResult['error'],
                ], 500);
            }

            // Create the order
            $order = Order::create([
                // Guest order fields
                'is_guest_order' => true,
                'guest_email' => $customerData['email'],
                'guest_phone' => $customerData['phone'],
                'guest_name' => $guestName,
                'guest_token' => $guestToken,

                // Shipping address
                'shipping_name' => $shippingName,
                'shipping_phone' => $shippingAddress['phone'],
                'shipping_email' => $shippingAddress['email'] ?? $customerData['email'],
                'shipping_address_line_1' => $shippingAddress['addressLine1'],
                'shipping_address_line_2' => $shippingAddress['addressLine2'] ?? null,
                'shipping_city' => $shippingAddress['city'],
                'shipping_state' => $shippingAddress['state'] ?? null,
                'shipping_postal_code' => $shippingAddress['postalCode'],
                'shipping_country' => $shippingAddress['country'],

                // Billing address
                'billing_name' => $billingName,
                'billing_phone' => $billingAddress['phone'] ?? $shippingAddress['phone'],
                'billing_email' => $billingAddress['email'] ?? $customerData['email'],
                'billing_address_line_1' => $billingAddress['addressLine1'] ?? $shippingAddress['addressLine1'],
                'billing_address_line_2' => $billingAddress['addressLine2'] ?? $shippingAddress['addressLine2'] ?? null,
                'billing_city' => $billingAddress['city'] ?? $shippingAddress['city'],
                'billing_state' => $billingAddress['state'] ?? $shippingAddress['state'] ?? null,
                'billing_postal_code' => $billingAddress['postalCode'] ?? $shippingAddress['postalCode'],
                'billing_country' => $billingAddress['country'] ?? $shippingAddress['country'],

                // Order details
                'customer_id' => $customer?->id,
                'sales_manager_id' => self::DEFAULT_SALES_MANAGER_ID,
                'shop_id' => self::DEFAULT_SHOP_ID,
                'sub_total' => $subTotal,
                'discount' => $discount,
                'total' => $total,
                'quantity' => $quantity,
                'paid_amount' => $paymentMethodData['type'] === 'cod' ? 0 : $total,
                'due_amount' => $paymentMethodData['type'] === 'cod' ? $total : 0,
                'order_status' => Order::STATUS_PENDING,
                'order_number' => $orderNumber,
                'payment_method_id' => $paymentMethod->id,
                'payment_status' => $paymentMethodData['type'] === 'cod' ? Order::UNPAID : Order::PAID,
                'shipment_status' => Order::SHIPMENT_STATUS_COMPLETED,
                'consignment_id' => $steadfastResult['data']['consignment_id'] ?? null,
                'tracking_code' => $steadfastResult['data']['tracking_code'] ?? null,

                // Additional info
                'order_notes' => $notes,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Create order details
            foreach ($orderDetailsData as $product) {
                OrderDetails::create([
                    'order_id' => $order->id,
                    'name' => $product->name,
                    'brand_id' => $product->brand_id,
                    'category_id' => $product->category_id,
                    'cost' => $product->cost,
                    'discount_end' => $product->discount_end,
                    'discount_fixed' => $product->discount_fixed,
                    'discount_percent' => $product->discount_percent,
                    'discount_start' => $product->discount_start,
                    'price' => $product->price,
                    'sale_price' => PriceManager::calculate_sell_price(
                        $product->price,
                        $product->discount_percent,
                        $product->discount_fixed,
                        $product->discount_start,
                        $product->discount_end
                    )['price'],
                    'sku' => $product->sku,
                    'sub_category_id' => $product->sub_category_id,
                    'child_sub_category_id' => $product->child_sub_category_id ?? null,
                    'supplier_id' => $product->supplier_id,
                    'quantity' => $product->quantity,
                    'photo' => $product->primary_photo?->photo,
                ]);
            }

            // Create transaction record
            $transaction = new Transaction();
            $transaction->order_id = $order->id;
            $transaction->customer_id = $customer?->id;
            $transaction->transactionable_type = 'guest_checkout';
            $transaction->transactionable_id = $order->id;
            $transaction->transaction_type = 1;
            $transaction->payment_method_id = $paymentMethod->id;
            $transaction->status = $paymentMethodData['type'] === 'cod' ? 2 : 1; // 2 = pending, 1 = completed
            $transaction->amount = $total;
            $transaction->save();

            DB::commit();

            // Log successful guest order
            Log::info('Guest order created', [
                'order_id' => $order->id,
                'order_number' => $orderNumber,
                'guest_email' => $customerData['email'],
                'total' => $total,
            ]);

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'orderNumber' => $order->order_number,
                        'guestToken' => $guestToken, // For order tracking
                        'status' => 'pending',
                        'paymentStatus' => $paymentMethodData['type'] === 'cod' ? 'unpaid' : 'paid',
                        'total' => $total,
                        'subTotal' => $subTotal,
                        'discount' => $discount,
                        'itemCount' => $quantity,
                        'trackingCode' => $order->tracking_code,
                        'consignmentId' => $order->consignment_id,
                    ],
                    'customer' => [
                        'email' => $customerData['email'],
                        'name' => $guestName,
                    ],
                    'shipping' => [
                        'name' => $shippingName,
                        'address' => $this->formatAddress($shippingAddress),
                    ],
                    'paymentMethod' => $paymentMethodData['type'],
                    'trackingUrl' => url("/api/guest/orders/track?token={$guestToken}"),
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            
            Log::error('Guest checkout failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['paymentMethod.cardNumber', 'paymentMethod.cvv']),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Order processing failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Track guest order by token
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function trackOrder(Request $request): JsonResponse
    {
        $token = $request->query('token');
        $orderNumber = $request->query('orderNumber');
        $email = $request->query('email');

        if ($token) {
            $order = Order::where('guest_token', $token)
                ->where('is_guest_order', true)
                ->first();
        } elseif ($orderNumber && $email) {
            $order = Order::where('order_number', $orderNumber)
                ->where('guest_email', $email)
                ->where('is_guest_order', true)
                ->first();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Please provide either a tracking token or order number with email',
            ], 400);
        }

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $order->load('order_details', 'payment_method');

        return response()->json([
            'success' => true,
            'data' => [
                'order' => [
                    'id' => $order->id,
                    'orderNumber' => $order->order_number,
                    'status' => $this->getOrderStatusLabel($order->order_status),
                    'paymentStatus' => $this->getPaymentStatusLabel($order->payment_status),
                    'trackingCode' => $order->tracking_code,
                    'consignmentId' => $order->consignment_id,
                    'total' => $order->total,
                    'subTotal' => $order->sub_total,
                    'discount' => $order->discount,
                    'createdAt' => $order->created_at->toIso8601String(),
                    'updatedAt' => $order->updated_at->toIso8601String(),
                ],
                'customer' => [
                    'name' => $order->guest_name,
                    'email' => $this->maskEmail($order->guest_email),
                    'phone' => $this->maskPhone($order->guest_phone),
                ],
                'shipping' => [
                    'name' => $order->shipping_name,
                    'address' => implode(', ', array_filter([
                        $order->shipping_address_line_1,
                        $order->shipping_city,
                        $order->shipping_state,
                        $order->shipping_postal_code,
                        $order->shipping_country,
                    ])),
                ],
                'items' => $order->order_details->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'sku' => $item->sku,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'salePrice' => $item->sale_price,
                        'photo' => $item->photo,
                    ];
                }),
                'paymentMethod' => $order->payment_method?->name,
            ],
        ]);
    }

    /**
     * Get guest order by order number and email (for email links)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderByEmailAndNumber(Request $request): JsonResponse
    {
        $request->validate([
            'orderNumber' => 'required|string',
            'email' => 'required|email',
        ]);

        $order = Order::where('order_number', $request->orderNumber)
            ->where('guest_email', $request->email)
            ->where('is_guest_order', true)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found. Please check your order number and email.',
            ], 404);
        }

        // Return limited tracking URL
        return response()->json([
            'success' => true,
            'data' => [
                'trackingUrl' => url("/api/guest/orders/track?token={$order->guest_token}"),
            ],
        ]);
    }

    /**
     * Validate cart items and prepare order details
     */
    private function validateAndPrepareCartItems(array $items): array
    {
        $subTotal = 0;
        $discount = 0;
        $total = 0;
        $quantity = 0;
        $orderDetailsData = [];

        foreach ($items as $item) {
            $productId = $item['id'];
            $itemQuantity = (int) $item['quantity'];

            // Find product by ID or SKU
            $product = Product::where('id', $productId)
                ->orWhere('sku', $productId)
                ->with('primary_photo')
                ->first();

            if (!$product) {
                return [
                    'success' => false,
                    'message' => "Product '{$productId}' not found",
                    'statusCode' => 404,
                ];
            }

            // Check stock
            if ($product->stock < $itemQuantity) {
                return [
                    'success' => false,
                    'message' => "Insufficient stock for '{$product->name}'. Available: {$product->stock}",
                    'statusCode' => 400,
                    'data' => [
                        'productId' => $productId,
                        'productName' => $product->name,
                        'available' => $product->stock,
                        'requested' => $itemQuantity,
                    ],
                ];
            }

            // Calculate prices
            $priceData = PriceManager::calculate_sell_price(
                $product->price,
                $product->discount_percent,
                $product->discount_fixed,
                $product->discount_start,
                $product->discount_end
            );

            $itemSubTotal = $product->price * $itemQuantity;
            $itemDiscount = $priceData['discount'] * $itemQuantity;
            $itemTotal = $priceData['price'] * $itemQuantity;

            $subTotal += $itemSubTotal;
            $discount += $itemDiscount;
            $total += $itemTotal;
            $quantity += $itemQuantity;

            // Store quantity on product for order details
            $product->quantity = $itemQuantity;
            $orderDetailsData[] = $product;

            // Decrement stock
            $product->decrement('stock', $itemQuantity);
        }

        return [
            'success' => true,
            'orderDetails' => $orderDetailsData,
            'subTotal' => $subTotal,
            'discount' => $discount,
            'total' => $total,
            'quantity' => $quantity,
        ];
    }

    /**
     * Find or create a guest customer record
     * This allows tracking repeat guest customers
     */
    private function findOrCreateGuestCustomer(array $customerData): ?Customer
    {
        // Try to find existing customer by email
        $customer = Customer::where('email', $customerData['email'])
            ->whereNull('user_id') // Guest customer (no user account)
            ->first();

        if (!$customer) {
            $customer = Customer::create([
                'name' => trim($customerData['firstName'] . ' ' . ($customerData['lastName'] ?? '')),
                'email' => $customerData['email'],
                'phone' => $customerData['phone'],
                'user_id' => null, // Guest - no user account
            ]);
        }

        return $customer;
    }

    /**
     * Resolve payment method from type string
     */
    private function resolvePaymentMethod(string $type): ?PaymentMethod
    {
        $typeMapping = [
            'cod' => 'Cash on Delivery',
            'online' => 'Online',
            'card' => 'Card',
            'bkash' => 'bKash',
            'nagad' => 'Nagad',
            'rocket' => 'Rocket',
        ];

        $searchTerm = $typeMapping[$type] ?? $type;

        $paymentMethod = PaymentMethod::where('name', 'like', '%' . $searchTerm . '%')->first();

        // Fallback to first available payment method
        if (!$paymentMethod) {
            $paymentMethod = PaymentMethod::first();
        }

        return $paymentMethod;
    }

    /**
     * Generate unique guest token for order tracking
     */
    private function generateGuestToken(): string
    {
        do {
            $token = Str::random(32) . bin2hex(random_bytes(16));
        } while (Order::where('guest_token', $token)->exists());

        return $token;
    }

    /**
     * Create order with Steadfast courier service
     */
    private function createSteadfastOrder(
        string $orderNumber,
        string $recipientName,
        string $recipientPhone,
        array $shippingAddress,
        int $total,
        ?string $notes,
        array $orderDetailsData,
        int $quantity,
        int $deliveryType,
        ?string $email
    ): array {
        try {
            $steadfastService = new SteadfastService();

            // Format address
            $addressParts = array_filter([
                $shippingAddress['addressLine1'],
                $shippingAddress['addressLine2'] ?? null,
                $shippingAddress['city'],
                $shippingAddress['state'] ?? null,
                $shippingAddress['country'],
                $shippingAddress['postalCode'],
            ]);
            $recipientAddress = implode(', ', $addressParts);
            if (strlen($recipientAddress) > 250) {
                $recipientAddress = substr($recipientAddress, 0, 247) . '...';
            }

            // Prepare item descriptions
            $itemDescriptions = collect($orderDetailsData)->map(function ($product) {
                return $product->name . ' (Qty: ' . $product->quantity . ')';
            })->implode(', ');

            // Clean phone number
            $cleanPhone = preg_replace('/[^0-9]/', '', $recipientPhone);
            if (strlen($cleanPhone) != 11) {
                $cleanPhone = '01234567890';
            }

            $steadfastOrderData = [
                'invoice' => $orderNumber,
                'recipient_name' => substr($recipientName, 0, 100),
                'recipient_phone' => $cleanPhone,
                'alternative_phone' => $cleanPhone,
                'recipient_address' => $recipientAddress,
                'cod_amount' => $total,
                'note' => $notes ? substr($notes, 0, 500) : null,
                'item_description' => substr($itemDescriptions, 0, 500),
                'total_lot' => $quantity,
                'delivery_type' => $deliveryType,
            ];

            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $steadfastOrderData['recipient_email'] = $email;
            }

            return $steadfastService->createOrder($steadfastOrderData);
        } catch (\Throwable $e) {
            Log::error('Steadfast order creation failed', [
                'error' => $e->getMessage(),
                'orderNumber' => $orderNumber,
            ]);

            // Return failure but don't block order if steadfast is down
            // You might want to make this configurable
            return [
                'success' => true, // Continue without Steadfast
                'data' => [
                    'consignment_id' => null,
                    'tracking_code' => null,
                ],
                'warning' => 'Delivery tracking not available',
            ];
        }
    }

    /**
     * Format address array to string
     */
    private function formatAddress(array $address): string
    {
        return implode(', ', array_filter([
            $address['addressLine1'] ?? null,
            $address['addressLine2'] ?? null,
            $address['city'] ?? null,
            $address['state'] ?? null,
            $address['postalCode'] ?? null,
            $address['country'] ?? null,
        ]));
    }

    /**
     * Get order status label
     */
    private function getOrderStatusLabel(int $status): string
    {
        return match ($status) {
            Order::STATUS_PENDING => 'pending',
            Order::STATUS_PROCESSED => 'processing',
            Order::STATUS_COMPLETED => 'completed',
            default => 'unknown',
        };
    }

    /**
     * Get payment status label
     */
    private function getPaymentStatusLabel(int $status): string
    {
        return match ($status) {
            Order::PAID => 'paid',
            Order::PARTIAL_PAID => 'partially_paid',
            Order::UNPAID => 'unpaid',
            default => 'unknown',
        };
    }

    /**
     * Mask email for privacy (guest@example.com -> g***@example.com)
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return '***@***.***';
        }

        $name = $parts[0];
        $domain = $parts[1];

        if (strlen($name) <= 2) {
            return $name[0] . '***@' . $domain;
        }

        return $name[0] . str_repeat('*', min(strlen($name) - 1, 5)) . '@' . $domain;
    }

    /**
     * Mask phone for privacy (01712345678 -> 017***5678)
     */
    private function maskPhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($clean) < 6) {
            return str_repeat('*', strlen($clean));
        }

        return substr($clean, 0, 3) . '***' . substr($clean, -4);
    }
}
