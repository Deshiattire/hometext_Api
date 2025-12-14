<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customerId' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'shippingAddress' => 'required|array',
            'shippingAddress.street' => 'required|string',
            'shippingAddress.city' => 'required|string',
            'shippingAddress.country' => 'required|string',
            'shippingAddress.postalCode' => 'required|string',
            'billingAddress' => 'required|array',
            'billingAddress.street' => 'required|string',
            'billingAddress.city' => 'required|string',
            'billingAddress.country' => 'required|string',
            'billingAddress.postalCode' => 'required|string',
            'paymentMethod' => 'required|array',
            'paymentMethod.type' => 'required|string',
        ];
    }
}
