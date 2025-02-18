<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Log;

class ShopProduct extends Pivot
{
    use HasFactory;
    protected $fillable = ['shop_id', 'product_id', 'quantity'];

    protected $table = 'shop_product';

    public function updateShopProduct($input, Product $product){
        $shopList = self::where('product_id', $product->id)->get();
        $array = json_decode(json_encode($shopList), true);

        $existingIds = array_column($array, 'shop_id');
        $requestIds = array_column($input, 'shop_id');

        // Perform insertion operation
        foreach ($input as $request) {
            if (!in_array($request['shop_id'], $existingIds)) {
                self::create([
                    'shop_id' => $request['shop_id'],
                    'product_id' => $product->id,
                    'quantity' => $request['quantity'],
                ]);
                // Log::info("Inserting ID: " . $request['shop_id']);
            } else {
                // Find existing data to check for updates
                foreach ($array as $existing) {
                    if ($existing['shop_id'] == $request['shop_id'] && $existing !== $request) {
                        $updateData = self::where("id", $existing['id'])->first();
                        $updateData->update([
                            'shop_id' => $existing['shop_id'],
                            'quantity' => $existing['quantity'],
                        ]);

                        // Update logic here
                        // Log::info("Updating ID: " . $request['shop_id']);
                    }
                }
            }
        }

        // Perform deletion operation
        foreach ($array as $existing) {
            if (!in_array($existing['shop_id'], $requestIds)) {
                self::where('product_id', $product->id)
                ->where('shop_id', $existing['shop_id'])
                ->delete();

                // Log::info("Deleting ID: " . $existing['shop_id']);
            }
        }

    }
}
