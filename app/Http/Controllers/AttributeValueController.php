<?php

namespace App\Http\Controllers;

use App\Models\AttributeValue;
use App\Http\Requests\StoreAttributeValueRequest;
use App\Http\Requests\UpdateAttributeValueRequest;
use Illuminate\Http\JsonResponse;

class AttributeValueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    final public function index(): JsonResponse
    {
        $values = AttributeValue::with('attribute:id,name')->orderBy('updated_at', 'desc')->get();
        return response()->json($values);
    }

    /**
     * @param StoreAttributeValueRequest $request
     * @return JsonResponse
     */
    final public function store(StoreAttributeValueRequest $request): JsonResponse
    {
        $value_data = $request->all();
//        $value_data['user_id']=auth()->id();
        AttributeValue::create($value_data);
        return response()->json(['msg'=>'Attribute Value Created Successfully', 'cls' => 'success']);
    }

    /**
     * Update the specified resource in storage.
     * @param UpdateAttributeValueRequest $request
     * @param AttributeValue $attributeValue
     * @return JsonResponse
     */
    final public function update(UpdateAttributeValueRequest $request, AttributeValue $attributeValue):JsonResponse
    {
        $value_data = $request->all();
        $attributeValue->update($value_data);
        return response()->json(['msg'=>'Attribute Value Updated Successfully', 'cls' => 'success']);
    }

    /**
     * @param AttributeValue $attributeValue
     * @return JsonResponse
     */
    final public function destroy(AttributeValue $attributeValue): JsonResponse
    {
        $attributeValue->delete();
        return response()->json(['msg'=>'Attribute Value Deleted Successfully', 'cls' => 'warning']);
    }
}
