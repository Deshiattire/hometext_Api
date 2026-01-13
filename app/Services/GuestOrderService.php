<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class GuestOrderService
{
    /**
     * Send order confirmation email to guest
     */
    public function sendOrderConfirmationEmail(Order $order): bool
    {
        if (!$order->is_guest_order || !$order->guest_email) {
            return false;
        }

        try {
            // You can implement your email sending logic here
            // For now, this is a placeholder that you can customize
            
            $data = [
                'orderNumber' => $order->order_number,
                'customerName' => $order->guest_name,
                'total' => $order->total,
                'trackingUrl' => url("/api/guest/orders/track?token={$order->guest_token}"),
                'items' => $order->order_details,
                'shippingAddress' => $this->formatShippingAddress($order),
            ];

            // Example using Laravel Mail (uncomment and customize):
            // Mail::to($order->guest_email)->send(new \App\Mail\GuestOrderConfirmation($data));

            Log::info('Order confirmation email sent', [
                'order_id' => $order->id,
                'email' => $order->guest_email,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send order confirmation email', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send order status update email to guest
     */
    public function sendOrderStatusUpdateEmail(Order $order, string $previousStatus): bool
    {
        if (!$order->is_guest_order || !$order->guest_email) {
            return false;
        }

        try {
            $data = [
                'orderNumber' => $order->order_number,
                'customerName' => $order->guest_name,
                'previousStatus' => $this->getStatusLabel($previousStatus),
                'currentStatus' => $this->getStatusLabel($order->order_status),
                'trackingUrl' => url("/api/guest/orders/track?token={$order->guest_token}"),
                'trackingCode' => $order->tracking_code,
            ];

            // Example using Laravel Mail (uncomment and customize):
            // Mail::to($order->guest_email)->send(new \App\Mail\GuestOrderStatusUpdate($data));

            Log::info('Order status update email sent', [
                'order_id' => $order->id,
                'email' => $order->guest_email,
                'new_status' => $order->order_status,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to send order status update email', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Find guest orders by email for account linking
     */
    public function findGuestOrdersByEmail(string $email): \Illuminate\Database\Eloquent\Collection
    {
        return Order::where('is_guest_order', true)
            ->where('guest_email', $email)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Link guest orders to user account after registration
     * This allows customers who register after placing guest orders to see their history
     */
    public function linkGuestOrdersToUser(string $email, int $userId): int
    {
        $linkedCount = 0;

        $guestOrders = Order::where('is_guest_order', true)
            ->where('guest_email', $email)
            ->whereNull('customer_id')
            ->orWhere(function ($query) use ($email) {
                $query->where('is_guest_order', true)
                    ->where('guest_email', $email);
            })
            ->get();

        foreach ($guestOrders as $order) {
            // Find or create customer record for this user
            $customer = \App\Models\Customer::firstOrCreate(
                ['user_id' => $userId],
                [
                    'name' => $order->guest_name,
                    'email' => $email,
                    'phone' => $order->guest_phone,
                ]
            );

            // Link order to customer (but keep guest data for reference)
            $order->customer_id = $customer->id;
            $order->save();

            $linkedCount++;
        }

        if ($linkedCount > 0) {
            Log::info('Guest orders linked to user account', [
                'user_id' => $userId,
                'email' => $email,
                'orders_linked' => $linkedCount,
            ]);
        }

        return $linkedCount;
    }

    /**
     * Convert guest order to registered user order (if customer creates account)
     */
    public function convertGuestToRegistered(Order $order, int $userId): bool
    {
        if (!$order->is_guest_order) {
            return false;
        }

        try {
            $customer = \App\Models\Customer::firstOrCreate(
                ['user_id' => $userId],
                [
                    'name' => $order->guest_name,
                    'email' => $order->guest_email,
                    'phone' => $order->guest_phone,
                ]
            );

            $order->customer_id = $customer->id;
            $order->save();

            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to convert guest order to registered', [
                'order_id' => $order->id,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get order by guest token
     */
    public function getOrderByGuestToken(string $token): ?Order
    {
        return Order::where('guest_token', $token)
            ->where('is_guest_order', true)
            ->with(['order_details', 'payment_method'])
            ->first();
    }

    /**
     * Get order by order number and email
     */
    public function getOrderByNumberAndEmail(string $orderNumber, string $email): ?Order
    {
        return Order::where('order_number', $orderNumber)
            ->where('guest_email', $email)
            ->where('is_guest_order', true)
            ->with(['order_details', 'payment_method'])
            ->first();
    }

    /**
     * Format shipping address from order
     */
    private function formatShippingAddress(Order $order): string
    {
        return implode(', ', array_filter([
            $order->shipping_address_line_1,
            $order->shipping_address_line_2,
            $order->shipping_city,
            $order->shipping_state,
            $order->shipping_postal_code,
            $order->shipping_country,
        ]));
    }

    /**
     * Get human-readable status label
     */
    private function getStatusLabel($status): string
    {
        return match ((int) $status) {
            Order::STATUS_PENDING => 'Pending',
            Order::STATUS_PROCESSED => 'Processing',
            Order::STATUS_COMPLETED => 'Completed',
            default => 'Unknown',
        };
    }
}
