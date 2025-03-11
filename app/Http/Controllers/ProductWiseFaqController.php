<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\ProductWiseFaq;
use Illuminate\Http\Request;

class ProductWiseFaqController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, $product_id=null)
    {
        $input = [
            'product_id' => $request->input('product_id'),
            'type' => $request->input('type')
        ];

        $faq = (new ProductWiseFaq())->getFAQList($input);


        return AppHelper::ResponseFormat(true, $faq->count() > 0? "Data found successfully." : "No data found", $faq);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductWiseFaq $productWiseFaq)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductWiseFaq $productWiseFaq)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductWiseFaq $productWiseFaq)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductWiseFaq $productWiseFaq)
    {
        //
    }
}
