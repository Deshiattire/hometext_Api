<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422)
        );
    }

    public function rules(): array
    {
        return [
            'customerId' => 'required|integer|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'shippingAddress' => 'required|array',
            'shippingAddress.street' => 'required|string|max:255',
            'shippingAddress.city' => 'required|string|max:100',
            'shippingAddress.state' => 'nullable|string|max:100',
            'shippingAddress.country' => 'required|string|max:100',
            'shippingAddress.postalCode' => 'required|string|max:20',
            'billingAddress' => 'required|array',
            'billingAddress.street' => 'required|string|max:255',
            'billingAddress.city' => 'required|string|max:100',
            'billingAddress.state' => 'nullable|string|max:100',
            'billingAddress.country' => 'required|string|max:100',
            'billingAddress.postalCode' => 'required|string|max:20',
            'paymentMethod' => 'required|array',
            'paymentMethod.type' => 'required|string|max:50',
            'paymentMethod.cardNumber' => 'nullable|string|max:50',
            'paymentMethod.expirationDate' => 'nullable|string|max:10',
            'paymentMethod.cvv' => 'nullable|string|max:10',
            'additionalDetails' => 'nullable|array',
            'additionalDetails.notes' => 'nullable|string|max:1000',
            'additionalDetails.couponCode' => 'nullable|string|max:50',
            'recipient' => 'nullable|array',
            'recipient.name' => 'nullable|string|max:100',
            'recipient.email' => 'nullable|email|max:255',
            'recipient.phone' => 'nullable|string|max:20',
            'recipient.alternative_phone' => 'nullable|string|max:20',
            'recipient.address' => 'nullable|string|max:500',
            'delivery_type' => 'nullable|numeric|in:0,1',
        ];
    }
}

