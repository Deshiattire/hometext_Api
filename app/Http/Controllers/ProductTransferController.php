<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TransferProduct;
use App\Models\ShopProduct;


class ProductTransferController extends Controller
{
    /**
     * Create a new product transfer.
     */
    public function store(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'product_id' => 'required|integer',
            'from_shop_id' => 'required|integer',
            'to_shop_id' => 'required|integer',
            'quantity' => 'required|integer',
        ]);

        // Create a new product transfer
        $transfer = TransferProduct::create($validatedData);

        // You can implement additional logic here, such as updating the shop_product table

        return response()->json(['message' => 'Product transfer created successfully', 'data' => $transfer], 201);
    }

    /**
     * List all product transfers.
     */
    public function index()
    {
        $transfers = TransferProduct::with('product','fromShop','toShop')->get();

        return response()->json(['data' => $transfers]);
    }

    /**
     * Show the details of a specific product transfer.
     */
    public function show(TransferProduct $transfer)
    {
        $data = [
            'id' => $transfer->id,
            'product_id' => $transfer->product_id,
            'product_name' => $transfer->product_name, // Get the product name via accessor
            'from_shop_id' => $transfer->from_shop_id,
            'from_shop_name' => $transfer->from_shop_name, // Get the from_shop name via accessor
            'to_shop_id' => $transfer->to_shop_id,
            'to_shop_name' => $transfer->to_shop_name, // Get the to_shop name via accessor
            'quantity' => $transfer->quantity,
            'status' => $transfer->status,
        ];

        return response()->json(['data' => $data]);
    }


    /**
     * Approve a product transfer.
     */
    public function approve(TransferProduct $transfer)
    {
        $transfer->update(['status' => 'approved']);

        // Additional logic to update shop_product
        $product = $transfer->product;
        $fromShop = $transfer->fromShop;
        $toShop = $transfer->toShop;

        // Update the quantity in the from_shop (decrease)
        $fromShopProduct = ShopProduct::where('product_id', $product->id)
            ->where('shop_id', $fromShop->id)
            ->first();

        if ($fromShopProduct) {
            $fromShopProduct->decrement('quantity', $transfer->quantity);
        } else {
            // Handle the case where the shop_product doesn't exist for the from_shop
            // You might want to add your own error handling or create the record here.
        }

        // Update the quantity in the to_shop (increase)
        $toShopProduct = ShopProduct::where('product_id', $product->id)
            ->where('shop_id', $toShop->id)
            ->first();

        if ($toShopProduct) {
            $toShopProduct->increment('quantity', $transfer->quantity);
        } else {
            // Create a new shop_product record if it doesn't exist
            ShopProduct::create([
                'product_id' => $product->id,
                'shop_id' => $toShop->id,
                'quantity' => $transfer->quantity,
            ]);
        }

        return response()->json(['message' => 'Product transfer approved successfully']);
    }

    /**
     * Reject a product transfer.
     */
    public function reject(TransferProduct $transfer)
    {
        $transfer->update(['status' => 'rejected']);

        return response()->json(['message' => 'Product transfer rejected']);
    }
}
