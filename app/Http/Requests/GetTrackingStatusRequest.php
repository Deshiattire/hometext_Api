<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class GetTrackingStatusRequest extends FormRequest
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
            'consignment_id' => 'nullable|string',
            'invoice' => 'nullable|string',
            'tracking_code' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'consignment_id.required_without_all' => 'At least one of consignment_id, invoice, or tracking_code is required',
            'invoice.required_without_all' => 'At least one of consignment_id, invoice, or tracking_code is required',
            'tracking_code.required_without_all' => 'At least one of consignment_id, invoice, or tracking_code is required',
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'consignment_id' => $this->input('consignment_id'),
            'invoice' => $this->input('invoice'),
            'tracking_code' => $this->input('tracking_code'),
        ]);
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $consignmentId = $this->input('consignment_id');
            $invoice = $this->input('invoice');
            $trackingCode = $this->input('tracking_code');

            if (empty($consignmentId) && empty($invoice) && empty($trackingCode)) {
                $validator->errors()->add('identifier', 'At least one of consignment_id, invoice, or tracking_code must be provided');
            }
        });
    }
}

