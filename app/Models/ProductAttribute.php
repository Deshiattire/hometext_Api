<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class ProductAttribute extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'attribute_id', 'attribute_value_id', 'attribute_math_sign', 'attribute_number', 'shop_quantities', 'attribute_weight', 'attribute_mesarment', 'attribute_cost'];

    protected $casts = [
        'shop_quantities' => 'array',
    ];

    /**
     * @param $input
     * @param Product $product
     * @return void
     */
    final public function storeAttribute($input, Product $product): void
    {
        $attribute_data = $this->prepareAttributeData($input, $product);
        foreach ($attribute_data as $attribute) {
            self::create($attribute);
        }
    }

    final public function updateAttribute($input, Product $product)
    {
        $attribute_data = $this->prepareAttributeData($input, $product);
        info($attribute_data);

        // Update existing attributes and add new attributes
        foreach ($attribute_data as $attribute) {
            $existingAttribute = $this->where('product_id', $product->id)
                ->where('attribute_id', $attribute['attribute_id'])
                ->first();

            if ($existingAttribute) {
                $existingAttribute->update([
                    'attribute_value_id' => $attribute['attribute_value_id'],
                ]);
            } else {
                self::create($attribute);
            }
        }
    }
    /**
     * @param array $input
     * @param Product $product
     * @return array
     */
    private function prepareAttributeData(array $input, Product $product): array
    {
        $attribute_data = [];
        foreach ($input as  $key => $value) {
            $data['product_id'] = $product->id;
            $data['attribute_id'] = $value['attribute_id'];
            $data['attribute_value_id'] = $value['value_id'];
            $data['attribute_math_sign'] = $value['math_sign'];
            $data['attribute_number'] = $value['number'];
            $data['shop_quantities'] = json_encode($value['shop_quantities']);
            $data['attribute_weight'] = $value['attribute_weight'];
            $data['attribute_mesarment'] = $value['attribute_mesarment'];
            $data['attribute_cost'] = $value['attribute_cost'];
            $attribute_data[] = $data;
        }

        return $attribute_data;
    }

    /**
     * @return BelongsTo
     */
    final public function attributes(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }

    /**
     * @return BelongsTo
     */
    final public function attribute_value(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class, 'attribute_value_id');
    }
}
