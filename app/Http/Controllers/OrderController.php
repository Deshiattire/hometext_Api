<?php

namespace App\Http\Controllers;

use App\Http\Resources\OrderDetailsResource;
use App\Http\Resources\OrderListResource;
use App\Models\Order;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Product;
use App\Models\OrderDetails;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\UserAddress;
use App\Manager\OrderManager;
use App\Manager\PriceManager;
use App\Services\SteadfastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $orders = (new Order())->getAllOrders($request->all(), auth());
        return OrderListResource::collection($orders);
    }

    /**
     * Get all orders by customer_id
     * 
     * @param Request $request
     * @param int|null $customer_id
     * @return JsonResponse
     */
    public function getOrdersByCustomer(Request $request, $customer_id = null): JsonResponse
    {
        try {
            $customerId = $customer_id ?? $request->input('customer_id');
            
            if (!$customerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer ID is required',
                ], 400);
            }

            if (!is_numeric($customerId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer ID must be a valid number',
                ], 400);
            }

            $customerId = (int) $customerId;

            $customer = Customer::find($customerId);
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found with the provided customer ID',
                    'customer_id' => $customerId,
                ], 404);
            }
            
            $orders = Order::where('customer_id', $customerId)
                ->with([
                    'customer:id,name,phone,email',
                    'payment_method:id,name',
                    'sales_manager:id,name',
                    'shop:id,name',
                    'order_details'
                ])
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'message' => 'Orders retrieved successfully',
                'data' => OrderListResource::collection($orders),
                'meta' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve orders: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @param StoreOrderRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreOrderRequest $request):JsonResponse
    {
        try {
            DB::beginTransaction();
        $order =(new Order)->placeOrder($request->all(), auth()->user());
        DB::commit();
            return response()->json(['msg'=>'Order Placed Successfully', 'cls' => 'success', 'flag'=>1, 'order_id'=>$order->id]);
        }catch (\Throwable $e){
            info('ORDER_PLACED_FAILED', ['message'=>$e->getMessage()]);
            DB::rollBack();
            return response()->json(['msg'=>$e->getMessage(), 'cls' => 'warning']);
        }
        // return $request->all();
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        $order->load([
            'customer',
            'payment_method',
            'sales_manager:id,name',
            'shop', 'order_details',
            'transactions',
            'transactions.customer',
            'transactions.payment_method',
            'transactions.transactionable',
        ]
        );
        return new  OrderDetailsResource($order);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        //
    }

    /**
     * Create a new order with the new API format
     * 
     * @param CreateOrderRequest $request
     * @return JsonResponse
     */
    public function createOrder(CreateOrderRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $customerUserId = $request->input('customerId');
            $items = $request->input('items');
            $shippingAddress = $request->input('shippingAddress');
            $billingAddress = $request->input('billingAddress');
            $paymentMethod = $request->input('paymentMethod');
            $additionalDetails = $request->input('additionalDetails', []);

            $customer = Customer::where('user_id', $customerUserId)->first();
            
            if (!$customer) {
                $user = \App\Models\User::find($customerUserId);
                if (!$user) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'User not found',
                    ], 404);
                }
                
                $customer = Customer::create([
                    'user_id' => $customerUserId,
                    'name' => $user->first_name . ($user->last_name ? ' ' . $user->last_name : ''),
                    'email' => $user->email,
                    'phone' => $user->phone ?? '',
                ]);
            }
            
            $customerId = $customer->id;

            $subTotal = 0;
            $discount = 0;
            $total = 0;
            $quantity = 0;
            $orderDetailsData = [];

            foreach ($items as $item) {
                $productId = $item['id'];
                $itemQuantity = $item['quantity'];

                $product = Product::where('sku', $productId)
                    ->orWhere('id', $productId)
                    ->with('primary_photo')
                    ->first();

                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Product with ID/SKU '{$productId}' not found",
                        'success' => false
                    ], 404);
                }

                if ($product->stock < $itemQuantity) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Insufficient stock for product '{$product->name}'. Available: {$product->stock}, Requested: {$itemQuantity}",
                        'success' => false
                    ], 400);
                }

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

                $product->quantity = $itemQuantity;
                $orderDetailsData[] = $product;

                $product->decrement('stock', $itemQuantity);
            }

            $paymentMethodModel = PaymentMethod::where('name', 'like', '%' . $paymentMethod['type'] . '%')
                ->first();

            if (!$paymentMethodModel) {
                $paymentMethodModel = PaymentMethod::first();
            }

            if (!$paymentMethodModel) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Payment method not found',
                    'success' => false
                ], 400);
            }

            $shopId = 4;
            $salesManagerId = 2;

            $order = Order::create([
                'customer_id' => $customerId,
                'sales_manager_id' => $salesManagerId,
                'shop_id' => $shopId,
                'sub_total' => $subTotal,
                'discount' => $discount,
                'total' => $total,
                'quantity' => $quantity,
                'paid_amount' => $total,
                'due_amount' => 0,
                'order_status' => Order::STATUS_PENDING,
                'order_number' => OrderManager::generateOrderNumber($shopId),
                'payment_method_id' => $paymentMethodModel->id,
                'payment_status' => Order::PAID,
                'shipment_status' => Order::SHIPMENT_STATUS_COMPLETED,
            ]);

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
                    'child_sub_category_id' => $product->child_sub_category_id,
                    'supplier_id' => $product->supplier_id,
                    'quantity' => $product->quantity,
                    'photo' => $product->primary_photo?->photo,
                ]);
            }

            if ($customer->user_id) {
                $shippingAddressString = json_encode($shippingAddress);
                $billingAddressString = json_encode($billingAddress);

                UserAddress::create([
                    'user_id' => $customer->user_id,
                    'address_type' => 'shipping',
                    'address_line_1' => $shippingAddress['street'],
                    'city' => $shippingAddress['city'],
                    'state' => $shippingAddress['state'] ?? null,
                    'postal_code' => $shippingAddress['postalCode'],
                    'country_code' => $this->getCountryCode($shippingAddress['country']),
                    'full_name' => $customer->name,
                    'phone' => $customer->phone ?? '',
                ]);

                if ($shippingAddressString !== $billingAddressString) {
                    UserAddress::create([
                        'user_id' => $customer->user_id,
                        'address_type' => 'billing',
                        'address_line_1' => $billingAddress['street'],
                        'city' => $billingAddress['city'],
                        'state' => $billingAddress['state'] ?? null,
                        'postal_code' => $billingAddress['postalCode'],
                        'country_code' => $this->getCountryCode($billingAddress['country']),
                        'full_name' => $customer->name,
                        'phone' => $customer->phone ?? '',
                    ]);
                }
            }

            DB::commit();

            $steadfastService = new SteadfastService();
            
            $itemDescriptions = $order->order_details->map(function ($detail) {
                return $detail->name . ' (Qty: ' . $detail->quantity . ')';
            })->implode(', ');

            $recipientData = $request->input('recipient', []);
            
            $recipientName = $recipientData['name'] ?? $customer->name;
            $recipientName = substr($recipientName, 0, 100);

            $recipientPhone = $recipientData['phone'] ?? $customer->phone ?? '01234567890';
            $recipientPhone = preg_replace('/[^0-9]/', '', $recipientPhone);
            if (strlen($recipientPhone) != 11) {
                $recipientPhone = '01234567890';
            }

            $recipientAddress = $recipientData['address'] ?? null;
            if (!$recipientAddress) {
                $addressParts = array_filter([
                    $shippingAddress['street'],
                    $shippingAddress['city'],
                    $shippingAddress['state'] ?? null,
                    $shippingAddress['country'],
                    $shippingAddress['postalCode']
                ]);
                $recipientAddress = implode(', ', $addressParts);
            }
            if (strlen($recipientAddress) > 250) {
                $recipientAddress = substr($recipientAddress, 0, 247) . '...';
            }

            $recipientEmail = null;
            if (isset($recipientData['email']) && filter_var($recipientData['email'], FILTER_VALIDATE_EMAIL)) {
                $recipientEmail = $recipientData['email'];
            } else {
                if ($customer->user_id) {
                    $user = \App\Models\User::find($customer->user_id);
                    if ($user && $user->email && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                        $recipientEmail = $user->email;
                    }
                }
                
                if (!$recipientEmail && $customer->email && filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
                    $recipientEmail = $customer->email;
                }
            }

            $steadfastOrderData = [
                'invoice' => $order->order_number,
                'recipient_name' => $recipientName,
                'recipient_phone' => $recipientPhone,
                'alternative_phone' => $recipientPhone,
                'recipient_address' => $recipientAddress,
                'cod_amount' => $total,
                'note' => isset($additionalDetails['notes']) ? substr($additionalDetails['notes'], 0, 500) : null,
                'item_description' => substr($itemDescriptions, 0, 500),
                'total_lot' => $quantity,
                'delivery_type' => 0,
            ];

            if ($recipientEmail) {
                $steadfastOrderData['recipient_email'] = $recipientEmail;
            }

            $steadfastResult = $steadfastService->createOrder($steadfastOrderData);

            if (!$steadfastResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order created but Steadfast integration failed',
                    'error' => $steadfastResult['error'],
                    'steadfast_status_code' => $steadfastResult['status_code'] ?? null,
                    'order' => [
                        'id' => $order->id,
                        'orderNumber' => $order->order_number,
                    ],
                ], 500);
            }

            $steadfastConsignment = $steadfastResult['data'];
            
            if ($steadfastConsignment && isset($steadfastConsignment['consignment_id'])) {
                $order->update([
                    'consignment_id' => $steadfastConsignment['consignment_id'],
                    'tracking_code' => $steadfastConsignment['tracking_code'] ?? null,
                ]);
            }

            $order->load(['customer', 'payment_method', 'order_details']);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'orderNumber' => $order->order_number,
                        'customerId' => $order->customer_id,
                        'customer' => [
                            'id' => $order->customer->id,
                            'name' => $order->customer->name,
                            'email' => $order->customer->email,
                            'phone' => $order->customer->phone,
                        ],
                        'items' => $order->order_details->map(function ($detail) {
                            return [
                                'id' => $detail->id,
                                'name' => $detail->name,
                                'sku' => $detail->sku,
                                'quantity' => $detail->quantity,
                                'price' => $detail->price,
                                'salePrice' => $detail->sale_price,
                            ];
                        }),
                        'shippingAddress' => $shippingAddress,
                        'billingAddress' => $billingAddress,
                        'paymentMethod' => [
                            'type' => $paymentMethod['type'],
                            'cardNumber' => $paymentMethod['cardNumber'] ?? null,
                        ],
                        'subTotal' => $order->sub_total,
                        'discount' => $order->discount,
                        'total' => $order->total,
                        'quantity' => $order->quantity,
                        'orderStatus' => $order->order_status,
                        'paymentStatus' => $order->payment_status,
                        'consignmentId' => $order->consignment_id,
                        'trackingCode' => $order->tracking_code,
                        'createdAt' => $order->created_at->toISOString(),
                        'updatedAt' => $order->updated_at->toISOString(),
                    ],
                ],
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'message' => $e->getMessage(),
                'success' => false
            ], 404);
        } catch (\Throwable $e) {
            DB::rollBack();
            info('ORDER_CREATE_FAILED', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Failed to create order: ' . $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    private function getCountryCode(string $countryName): string
    {
        $countryMap = [
            'Bangladesh' => 'BD',
            'BD' => 'BD',
            'United States' => 'US',
            'US' => 'US',
            'United Kingdom' => 'GB',
            'UK' => 'GB',
            'Country' => 'BD',
        ];

        return $countryMap[$countryName] ?? 'BD';
    }

    /**
     * Get order details by invoice ID or order number
     * 
     * @param string $invoiceId
     * @return JsonResponse
     */
    public function getOrderByInvoice($invoiceId): JsonResponse
    {
        try {
            if (!$invoiceId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice ID or Order Number is required',
                ], 400);
            }

            $order = Order::where('order_number', $invoiceId)
                ->with([
                    'customer:id,name,phone,email',
                    'payment_method:id,name',
                    'sales_manager:id,name',
                    'shop:id,name',
                    'order_details.brand:id,name',
                    'order_details.category:id,name',
                    'order_details.sub_category:id,name',
                    'order_details.supplier:id,name',
                    'transactions',
                    'transactions.customer',
                    'transactions.payment_method',
                ])
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found with the provided invoice ID or order number',
                    'invoice_id' => $invoiceId,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Order details retrieved successfully',
                'data' => new OrderDetailsResource($order),
            ], 200);
        } catch (\Throwable $e) {
            info('GET_ORDER_BY_INVOICE_FAILED', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve order details: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get delivery status from Steadfast by consignment_id, invoice, or tracking_code
     * 
     * @param \App\Http\Requests\GetTrackingStatusRequest $request
     * @return JsonResponse
     */
    public function getTrackingStatus(\App\Http\Requests\GetTrackingStatusRequest $request): JsonResponse
    {
        try {
            $steadfastService = new SteadfastService();
            $consignmentId = $request->input('consignment_id');
            $invoice = $request->input('invoice');
            $trackingCode = $request->input('tracking_code');

            $statusData = null;
            $identifierType = null;
            $identifierValue = null;

            if ($consignmentId) {
                $statusData = $steadfastService->getStatusByConsignmentId($consignmentId);
                $identifierType = 'consignment_id';
                $identifierValue = $consignmentId;
            } elseif ($invoice) {
                $statusData = $steadfastService->getStatusByInvoice($invoice);
                $identifierType = 'invoice';
                $identifierValue = $invoice;
                
                if ($statusData && isset($statusData['status']) && $statusData['status'] == 200) {
                    $order = Order::where('order_number', $invoice)->first();
                    if ($order) {
                        $statusData['order'] = [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'consignment_id' => $order->consignment_id,
                            'tracking_code' => $order->tracking_code,
                            'total' => $order->total,
                            'customer_id' => $order->customer_id,
                        ];
                    }
                }
            } elseif ($trackingCode) {
                $statusData = $steadfastService->getStatusByTrackingCode($trackingCode);
                $identifierType = 'tracking_code';
                $identifierValue = $trackingCode;
                
                if ($statusData && isset($statusData['status']) && $statusData['status'] == 200) {
                    $order = Order::where('tracking_code', $trackingCode)->first();
                    if ($order) {
                        $statusData['order'] = [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'consignment_id' => $order->consignment_id,
                            'tracking_code' => $order->tracking_code,
                            'total' => $order->total,
                            'customer_id' => $order->customer_id,
                        ];
                    }
                }
            }

            if (!$statusData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to retrieve delivery status from Steadfast',
                    'identifier_type' => $identifierType,
                    'identifier_value' => $identifierValue,
                ], 404);
            }

            if (isset($statusData['status']) && $statusData['status'] == 200) {
                return response()->json([
                    'success' => true,
                    'message' => 'Delivery status retrieved successfully',
                    'data' => [
                        'delivery_status' => $statusData['delivery_status'] ?? null,
                        'status' => $statusData['status'] ?? null,
                        'identifier_type' => $identifierType,
                        'identifier_value' => $identifierValue,
                        'order' => $statusData['order'] ?? null,
                    ],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $statusData['message'] ?? 'Failed to retrieve delivery status',
                'data' => $statusData,
            ], 400);

        } catch (\Throwable $e) {
            info('TRACKING_STATUS_FAILED', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tracking status: ' . $e->getMessage(),
            ], 500);
        }
    }
}
