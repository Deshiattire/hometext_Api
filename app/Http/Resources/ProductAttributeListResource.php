<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
// Assume Shop is your model which contains shop details
use App\Models\Shop;

class ProductAttributeListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $shopQuantities = $this->processShopQuantities();

        // Format shop quantities with shop names
        $formattedShopQuantities = [];
        foreach ($shopQuantities as $shopId => $quantity) {
            $formattedShopQuantities[] = [
                'shop_id' => $shopId,
                'shop_name' => $this->getShopNameById($shopId), // Retrieve shop name by ID
                'quantity' => $quantity,
            ];
        }

        return [
            'id' => $this->id,
            'attribute_id' => $this->attributes?->id,
            'attribute_value_id' => $this->attribute_value?->id,
            'attribute_name' => $this->attributes?->name,
            'attribute_value' => $this->attribute_value?->name,
            'math_sign' => $this->attribute_math_sign,
            'number' => $this->attribute_number,
            'shop_quantities' => $formattedShopQuantities,
            'weight' => $this->attribute_weight,
            'measurement' => $this->attribute_measurement, // Corrected typo
            'cost' => $this->attribute_cost,
        ];
    }

    protected function processShopQuantities()
    {
        $shopQuantities = [];

        if (is_string($this->shop_quantities)) {
            $decoded = json_decode($this->shop_quantities, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $shopQuantities = $decoded;
            } else {
                Log::error('JSON decoding error: ' . json_last_error_msg());
            }
        } elseif (is_array($this->shop_quantities)) {
            $shopQuantities = $this->shop_quantities;
        } else {
            Log::error('Unexpected type of shop_quantities.', ['shop_quantities' => $this->shop_quantities]);
        }

        return $shopQuantities;
    }

    // Dummy method to get shop name by ID, replace with your actual logic
    protected function getShopNameById($shopId)
    {
        // Assuming Shop is your model that contains shop details
        // Replace this with your actual logic to retrieve shop name
        $shop = Shop::find($shopId);
        return $shop ? $shop->name : 'Unknown Shop';
    }
}
