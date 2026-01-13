# Guest Checkout API Documentation

## Overview

The Guest Checkout system allows customers to place orders without creating an account. This follows e-commerce best practices where reducing friction in the checkout process leads to higher conversion rates.

## Features

- ✅ No account required to place orders
- ✅ Secure order tracking via unique token
- ✅ Order lookup by email + order number
- ✅ Full address management (shipping & billing)
- ✅ Multiple payment method support
- ✅ Steadfast courier integration
- ✅ Stock validation and management
- ✅ Privacy-conscious data masking
- ✅ Account linking after registration

## API Endpoints

### 1. Guest Checkout (Place Order)

**Endpoint:** `POST /api/guest/checkout`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "customer": {
    "email": "guest@example.com",
    "phone": "01712345678",
    "firstName": "John",
    "lastName": "Doe",
    "acceptsMarketing": false
  },
  "items": [
    {
      "id": "SKU123",
      "quantity": 2
    },
    {
      "id": 45,
      "quantity": 1
    }
  ],
  "shippingAddress": {
    "firstName": "John",
    "lastName": "Doe",
    "phone": "01712345678",
    "email": "guest@example.com",
    "addressLine1": "123 Main Street",
    "addressLine2": "Apt 4B",
    "city": "Dhaka",
    "state": "Dhaka Division",
    "postalCode": "1205",
    "country": "Bangladesh"
  },
  "billingAddress": {
    "firstName": "John",
    "lastName": "Doe",
    "phone": "01712345678",
    "addressLine1": "123 Main Street",
    "city": "Dhaka",
    "postalCode": "1205",
    "country": "Bangladesh"
  },
  "billingSameAsShipping": true,
  "paymentMethod": {
    "type": "cod",
    "transactionId": null
  },
  "shippingMethod": {
    "id": "standard",
    "name": "Standard Delivery",
    "cost": 60
  },
  "notes": "Please call before delivery",
  "couponCode": null,
  "deliveryType": 0
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Order placed successfully",
  "data": {
    "order": {
      "id": 125,
      "orderNumber": "HTB260109123456",
      "guestToken": "abc123xyz789...",
      "status": "pending",
      "paymentStatus": "unpaid",
      "total": 5000,
      "subTotal": 5500,
      "discount": 500,
      "itemCount": 3,
      "trackingCode": "ST123456",
      "consignmentId": "CON123456"
    },
    "customer": {
      "email": "guest@example.com",
      "name": "John Doe"
    },
    "shipping": {
      "name": "John Doe",
      "address": "123 Main Street, Apt 4B, Dhaka, Dhaka Division, 1205, Bangladesh"
    },
    "paymentMethod": "cod",
    "trackingUrl": "https://your-domain.com/api/guest/orders/track?token=abc123xyz789..."
  }
}
```

**Validation Error Response (422):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "customer.email": ["Email address is required for order confirmation."],
    "items": ["Your cart is empty. Please add items before checkout."]
  }
}
```

### 2. Track Guest Order

**Endpoint:** `GET /api/guest/orders/track`

**Query Parameters:**
- `token` - The guest token received after checkout
- OR `orderNumber` + `email` - Order number and guest email

**Example with Token:**
```
GET /api/guest/orders/track?token=abc123xyz789...
```

**Example with Order Number & Email:**
```
GET /api/guest/orders/track?orderNumber=HTB260109123456&email=guest@example.com
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "order": {
      "id": 125,
      "orderNumber": "HTB260109123456",
      "status": "processing",
      "paymentStatus": "paid",
      "trackingCode": "ST123456",
      "consignmentId": "CON123456",
      "total": 5000,
      "subTotal": 5500,
      "discount": 500,
      "createdAt": "2026-01-09T10:30:00+06:00",
      "updatedAt": "2026-01-09T11:00:00+06:00"
    },
    "customer": {
      "name": "John Doe",
      "email": "j*****@example.com",
      "phone": "017***5678"
    },
    "shipping": {
      "name": "John Doe",
      "address": "123 Main Street, Dhaka, Dhaka Division, 1205, Bangladesh"
    },
    "items": [
      {
        "name": "Product A",
        "sku": "SKU123",
        "quantity": 2,
        "price": 2000,
        "salePrice": 1800,
        "photo": "products/photo.jpg"
      }
    ],
    "paymentMethod": "Cash on Delivery"
  }
}
```

### 3. Order Lookup (Get Tracking URL)

**Endpoint:** `POST /api/guest/orders/lookup`

This endpoint is useful for "Forgot Order" scenarios where customers need to retrieve their tracking link.

**Request Body:**
```json
{
  "orderNumber": "HTB260109123456",
  "email": "guest@example.com"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "trackingUrl": "https://your-domain.com/api/guest/orders/track?token=abc123xyz789..."
  }
}
```

## Payment Methods Supported

| Type | Description |
|------|-------------|
| `cod` | Cash on Delivery |
| `online` | Online Payment |
| `card` | Credit/Debit Card |
| `bkash` | bKash Mobile Banking |
| `nagad` | Nagad Mobile Banking |
| `rocket` | Rocket Mobile Banking |

## Delivery Types

| Value | Description |
|-------|-------------|
| `0` | Standard Delivery |
| `1` | Express Delivery |

## Best Practices for Frontend Integration

### 1. Cart Validation
Always validate cart items on the frontend before checkout:
```javascript
const validateCart = (items) => {
  if (!items || items.length === 0) {
    return { valid: false, error: 'Cart is empty' };
  }
  
  for (const item of items) {
    if (!item.id || !item.quantity || item.quantity < 1) {
      return { valid: false, error: 'Invalid cart item' };
    }
  }
  
  return { valid: true };
};
```

### 2. Store Guest Token
After successful checkout, store the guest token for order tracking:
```javascript
const handleCheckoutSuccess = (response) => {
  const { guestToken, orderNumber } = response.data.order;
  
  // Store in localStorage or sessionStorage
  localStorage.setItem('guestOrderToken', guestToken);
  localStorage.setItem('lastOrderNumber', orderNumber);
  
  // Redirect to confirmation page
  router.push(`/order-confirmation?order=${orderNumber}`);
};
```

### 3. Guest Order Tracking Page
Implement a tracking page that works with either token or email lookup:
```javascript
const trackOrder = async () => {
  const token = localStorage.getItem('guestOrderToken');
  
  if (token) {
    const response = await fetch(`/api/guest/orders/track?token=${token}`);
    return response.json();
  }
  
  // Fallback to email + order number lookup
  if (email && orderNumber) {
    const response = await fetch(`/api/guest/orders/track?orderNumber=${orderNumber}&email=${email}`);
    return response.json();
  }
};
```

### 4. Link Orders After Registration
When a guest user creates an account, call the API to link their previous orders:
```javascript
// After successful registration/login with same email
const linkGuestOrders = async (userEmail) => {
  // This would be a backend call that uses GuestOrderService::linkGuestOrdersToUser()
  // The backend should automatically detect and link orders
};
```

## Database Migration

Run the migration to add guest checkout fields:
```bash
php artisan migrate
```

This will add the following fields to the `orders` table:
- `is_guest_order` - Boolean flag
- `guest_email`, `guest_phone`, `guest_name` - Guest contact info
- `guest_token` - Unique tracking token
- `shipping_*` and `billing_*` - Address fields
- `order_notes`, `ip_address`, `user_agent` - Additional tracking

## Security Considerations

1. **Token Security**: Guest tokens are unique 64-character strings that cannot be guessed
2. **Email Masking**: Customer emails are masked in tracking responses (j***@example.com)
3. **Phone Masking**: Phone numbers are partially hidden (017***5678)
4. **Rate Limiting**: Consider adding rate limiting to prevent abuse
5. **HTTPS**: Always use HTTPS in production

## Error Handling

| Status Code | Description |
|-------------|-------------|
| 201 | Order created successfully |
| 400 | Bad request (invalid payment method, insufficient stock) |
| 404 | Product or order not found |
| 422 | Validation failed |
| 500 | Server error |

## Testing with cURL

### Place Guest Order
```bash
curl -X POST http://your-domain.com/api/guest/checkout \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer": {
      "email": "test@example.com",
      "phone": "01712345678",
      "firstName": "Test",
      "lastName": "User"
    },
    "items": [{"id": 1, "quantity": 1}],
    "shippingAddress": {
      "firstName": "Test",
      "lastName": "User",
      "phone": "01712345678",
      "addressLine1": "123 Test Street",
      "city": "Dhaka",
      "postalCode": "1205",
      "country": "Bangladesh"
    },
    "paymentMethod": {"type": "cod"}
  }'
```

### Track Order
```bash
curl "http://your-domain.com/api/guest/orders/track?token=YOUR_GUEST_TOKEN"
```
