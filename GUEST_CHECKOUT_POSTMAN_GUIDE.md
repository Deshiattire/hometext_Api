# Guest Checkout API - Postman Testing Guide

## Base URL
```
http://127.0.0.1:8000/api
```

---

## 1. Place Guest Order (Cash on Delivery)

**Endpoint:** `POST /api/guest/checkout`

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (Raw JSON):**
```json
{
    "customer": {
        "email": "guest.customer@example.com",
        "phone": "01712345678",
        "firstName": "John",
        "lastName": "Doe",
        "acceptsMarketing": false
    },
    "items": [
        {
            "id": 30,
            "quantity": 2
        },
        {
            "id": 31,
            "quantity": 1
        }
    ],
    "shippingAddress": {
        "firstName": "John",
        "lastName": "Doe",
        "phone": "01712345678",
        "email": "guest.customer@example.com",
        "addressLine1": "House 123, Road 5, Block A",
        "addressLine2": "Banani",
        "city": "Dhaka",
        "state": "Dhaka Division",
        "postalCode": "1213",
        "country": "Bangladesh"
    },
    "billingSameAsShipping": true,
    "paymentMethod": {
        "type": "cod",
        "transactionId": null
    },
    "notes": "Please call before delivery. Gate code is 1234.",
    "deliveryType": 0
}
```

**Expected Success Response (201):**
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
            "email": "guest.customer@example.com",
            "name": "John Doe"
        },
        "shipping": {
            "name": "John Doe",
            "address": "House 123, Road 5, Block A, Banani, Dhaka, Dhaka Division, 1213, Bangladesh"
        },
        "paymentMethod": "cod",
        "trackingUrl": "http://127.0.0.1:8000/api/guest/orders/track?token=abc123xyz789..."
    }
}
```

---

## 2. Place Guest Order (bKash Payment)

**Endpoint:** `POST /api/guest/checkout`

**Body (Raw JSON):**
```json
{
    "customer": {
        "email": "bkash.user@example.com",
        "phone": "01812345678",
        "firstName": "Rahim",
        "lastName": "Ahmed",
        "acceptsMarketing": true
    },
    "items": [
        {
            "id": 32,
            "quantity": 1
        }
    ],
    "shippingAddress": {
        "firstName": "Rahim",
        "lastName": "Ahmed",
        "phone": "01812345678",
        "email": "bkash.user@example.com",
        "addressLine1": "Flat 4B, Sunrise Tower",
        "addressLine2": "Gulshan-2",
        "city": "Dhaka",
        "state": "Dhaka Division",
        "postalCode": "1212",
        "country": "Bangladesh"
    },
    "billingSameAsShipping": true,
    "paymentMethod": {
        "type": "bkash",
        "transactionId": "TXN123456789"
    },
    "notes": "Gift wrap please",
    "deliveryType": 1
}
```

---

## 3. Minimal Guest Order (Only Required Fields)

**Endpoint:** `POST /api/guest/checkout`

**Body (Raw JSON):**
```json
{
    "customer": {
        "email": "minimal@example.com",
        "phone": "01912345678",
        "firstName": "Karim"
    },
    "items": [
        {
            "id": 39,
            "quantity": 1
        }
    ],
    "shippingAddress": {
        "firstName": "Karim",
        "phone": "01912345678",
        "addressLine1": "123 Test Street",
        "city": "Dhaka",
        "postalCode": "1000",
        "country": "Bangladesh"
    },
    "paymentMethod": {
        "type": "cod"
    }
}
```

---

## 4. Track Order by Token

**Endpoint:** `GET /api/guest/orders/track`

**Query Parameters:**
- `token`: The `guestToken` from checkout response

**Example URL:**
```
GET http://127.0.0.1:8000/api/guest/orders/track?token=YOUR_GUEST_TOKEN_HERE
```

**Expected Response (200):**
```json
{
    "success": true,
    "data": {
        "order": {
            "id": 125,
            "orderNumber": "HTB260109123456",
            "status": "pending",
            "paymentStatus": "unpaid",
            "trackingCode": "ST123456",
            "consignmentId": "CON123456",
            "total": 5000,
            "subTotal": 5500,
            "discount": 500,
            "createdAt": "2026-01-09T10:30:00+06:00",
            "updatedAt": "2026-01-09T10:30:00+06:00"
        },
        "customer": {
            "name": "John Doe",
            "email": "g*****@example.com",
            "phone": "017***5678"
        },
        "shipping": {
            "name": "John Doe",
            "address": "House 123, Road 5, Block A, Dhaka, Dhaka Division, 1213, Bangladesh"
        },
        "items": [
            {
                "name": "Product Name",
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

---

## 5. Track Order by Email + Order Number

**Endpoint:** `GET /api/guest/orders/track`

**Query Parameters:**
- `orderNumber`: Order number from checkout response
- `email`: Customer email used during checkout

**Example URL:**
```
GET http://127.0.0.1:8000/api/guest/orders/track?orderNumber=HTB260109123456&email=guest.customer@example.com
```

---

## 6. Lookup Order (Get Tracking URL)

**Endpoint:** `POST /api/guest/orders/lookup`

**Use Case:** When customer lost their tracking token but knows email + order number

**Body (Raw JSON):**
```json
{
    "orderNumber": "HTB260109123456",
    "email": "guest.customer@example.com"
}
```

**Expected Response (200):**
```json
{
    "success": true,
    "data": {
        "trackingUrl": "http://127.0.0.1:8000/api/guest/orders/track?token=abc123xyz789..."
    }
}
```

---

## Payment Methods Available

| Type | Description |
|------|-------------|
| `cod` | Cash on Delivery |
| `online` | Online Payment |
| `card` | Credit/Debit Card |
| `bkash` | bKash Mobile Banking |
| `nagad` | Nagad Mobile Banking |
| `rocket` | Rocket Mobile Banking |

---

## Delivery Types

| Value | Description |
|-------|-------------|
| `0` | Standard Delivery |
| `1` | Express Delivery |

---

## Common Validation Errors

### Missing Email (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "customer.email": ["Email address is required for order confirmation."]
    }
}
```

### Empty Cart (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "items": ["Your cart is empty. Please add items before checkout."]
    }
}
```

### Invalid Payment Method (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "paymentMethod.type": ["Invalid payment method selected."]
    }
}
```

### Product Not Found (404)
```json
{
    "success": false,
    "message": "Product '999' not found",
    "statusCode": 404
}
```

### Insufficient Stock (400)
```json
{
    "success": false,
    "message": "Insufficient stock for 'Product Name'. Available: 5",
    "statusCode": 400,
    "data": {
        "productId": 1,
        "productName": "Product Name",
        "available": 5,
        "requested": 10
    }
}
```

---

## cURL Examples

### Place Order
```bash
curl -X POST http://127.0.0.1:8000/api/guest/checkout \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "customer": {
      "email": "test@example.com",
      "phone": "01712345678",
      "firstName": "Test"
    },
    "items": [{"id": 30, "quantity": 1}],
    "shippingAddress": {
      "firstName": "Test",
      "phone": "01712345678",
      "addressLine1": "123 Street",
      "city": "Dhaka",
      "postalCode": "1000",
      "country": "Bangladesh"
    },
    "paymentMethod": {"type": "cod"}
  }'
```

### Track Order
```bash
curl "http://127.0.0.1:8000/api/guest/orders/track?token=YOUR_TOKEN"
```
