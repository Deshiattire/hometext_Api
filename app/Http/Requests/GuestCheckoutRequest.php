<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class GuestCheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Guest checkout is always authorized (no authentication required)
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Guest Customer Information
            'customer.email' => 'required|email|max:255',
            'customer.phone' => 'required|string|min:10|max:20',
            'customer.firstName' => 'required|string|max:100',
            'customer.lastName' => 'nullable|string|max:100',
            'customer.acceptsMarketing' => 'nullable|boolean',

            // Cart Items
            'items' => 'required|array|min:1',
            'items.*.id' => 'required', // Can be product ID or SKU
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.price' => 'nullable|numeric|min:0', // Optional, server will verify
            'items.*.name' => 'nullable|string', // Optional, for display
            'items.*.sku' => 'nullable|string', // Optional SKU

            // Shipping Address
            'shippingAddress' => 'required|array',
            'shippingAddress.firstName' => 'required|string|max:100',
            'shippingAddress.lastName' => 'nullable|string|max:100',
            'shippingAddress.phone' => 'required|string|min:10|max:20',
            'shippingAddress.email' => 'nullable|email|max:255',
            'shippingAddress.addressLine1' => 'required|string|max:255',
            'shippingAddress.addressLine2' => 'nullable|string|max:255',
            'shippingAddress.city' => 'required|string|max:100',
            'shippingAddress.state' => 'nullable|string|max:100',
            'shippingAddress.postalCode' => 'required|string|max:20',
            'shippingAddress.country' => 'required|string|max:100',

            // Billing Address (optional - uses shipping if not provided)
            'billingAddress' => 'nullable|array',
            'billingAddress.firstName' => 'required_with:billingAddress|string|max:100',
            'billingAddress.lastName' => 'nullable|string|max:100',
            'billingAddress.phone' => 'required_with:billingAddress|string|min:10|max:20',
            'billingAddress.email' => 'nullable|email|max:255',
            'billingAddress.addressLine1' => 'required_with:billingAddress|string|max:255',
            'billingAddress.addressLine2' => 'nullable|string|max:255',
            'billingAddress.city' => 'required_with:billingAddress|string|max:100',
            'billingAddress.state' => 'nullable|string|max:100',
            'billingAddress.postalCode' => 'required_with:billingAddress|string|max:20',
            'billingAddress.country' => 'required_with:billingAddress|string|max:100',
            
            // Flag to indicate if billing same as shipping
            'billingSameAsShipping' => 'nullable|boolean',

            // Payment Information
            'paymentMethod' => 'required|array',
            'paymentMethod.type' => 'required|string|in:cod,online,card,bkash,nagad,rocket',
            'paymentMethod.transactionId' => 'nullable|string|max:100',

            // Shipping Method
            'shippingMethod' => 'nullable|array',
            'shippingMethod.id' => 'nullable|string',
            'shippingMethod.name' => 'nullable|string',
            'shippingMethod.cost' => 'nullable|numeric|min:0',

            // Additional Details
            'notes' => 'nullable|string|max:1000',
            'couponCode' => 'nullable|string|max:50',
            'voucherCode' => 'nullable|string|max:50',
            
            // Delivery preference
            'deliveryType' => 'nullable|integer|in:0,1', // 0 = standard, 1 = express
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer.email.required' => 'Email address is required for order confirmation.',
            'customer.email.email' => 'Please provide a valid email address.',
            'customer.phone.required' => 'Phone number is required for delivery updates.',
            'customer.phone.min' => 'Phone number must be at least 10 digits.',
            'customer.firstName.required' => 'First name is required.',
            
            'items.required' => 'Your cart is empty. Please add items before checkout.',
            'items.min' => 'Your cart is empty. Please add items before checkout.',
            'items.*.id.required' => 'Product ID is missing for one or more items.',
            'items.*.quantity.required' => 'Quantity is required for all items.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.quantity.max' => 'Maximum quantity per item is 100.',

            'shippingAddress.required' => 'Shipping address is required.',
            'shippingAddress.firstName.required' => 'Recipient name is required.',
            'shippingAddress.phone.required' => 'Delivery phone number is required.',
            'shippingAddress.addressLine1.required' => 'Street address is required.',
            'shippingAddress.city.required' => 'City is required.',
            'shippingAddress.postalCode.required' => 'Postal code is required.',
            'shippingAddress.country.required' => 'Country is required.',

            'paymentMethod.required' => 'Payment method is required.',
            'paymentMethod.type.required' => 'Payment type is required.',
            'paymentMethod.type.in' => 'Invalid payment method selected.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'customer.email' => 'email',
            'customer.phone' => 'phone number',
            'customer.firstName' => 'first name',
            'customer.lastName' => 'last name',
            'shippingAddress.firstName' => 'recipient first name',
            'shippingAddress.lastName' => 'recipient last name',
            'shippingAddress.phone' => 'delivery phone',
            'shippingAddress.addressLine1' => 'street address',
            'shippingAddress.addressLine2' => 'address line 2',
            'shippingAddress.city' => 'city',
            'shippingAddress.state' => 'state/province',
            'shippingAddress.postalCode' => 'postal code',
            'shippingAddress.country' => 'country',
            'billingAddress.firstName' => 'billing first name',
            'billingAddress.addressLine1' => 'billing street address',
            'paymentMethod.type' => 'payment method',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize phone numbers - remove common formatting
        if ($this->has('customer.phone')) {
            $phone = preg_replace('/[^\d+]/', '', $this->input('customer.phone'));
            $this->merge([
                'customer' => array_merge($this->input('customer', []), ['phone' => $phone])
            ]);
        }

        if ($this->has('shippingAddress.phone')) {
            $phone = preg_replace('/[^\d+]/', '', $this->input('shippingAddress.phone'));
            $shippingAddress = $this->input('shippingAddress', []);
            $shippingAddress['phone'] = $phone;
            $this->merge(['shippingAddress' => $shippingAddress]);
        }

        // If billing same as shipping, copy shipping to billing
        if ($this->input('billingSameAsShipping', true) && !$this->has('billingAddress')) {
            $this->merge([
                'billingAddress' => $this->input('shippingAddress')
            ]);
        }
    }
}
