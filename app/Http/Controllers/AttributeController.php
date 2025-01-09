<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Http\Requests\StoreAttributeRequest;
use App\Http\Requests\UpdateAttributeRequest;
use App\Http\Resources\AttributeListResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttributeController extends Controller
{
    /**
     * @return AnonymousResourceCollection
     */
    final public function index():AnonymousResourceCollection
    {
        $attributes =(new Attribute())->getAttributeList();
        return AttributeListResource::collection($attributes);
    }

    /**
     * @param StoreAttributeRequest $request
     * @return JsonResponse
     */
    final public function store(StoreAttributeRequest $request):JsonResponse
    {
        $attribute_data = $request->all();
//        $attribute_data['user_id']=auth()->id();
        Attribute::create($attribute_data);
        return response()->json(['msg'=>'Attribute Created Successfully', 'cls' => 'success']);
    }

    /**
     * @param UpdateAttributeRequest $request
     * @param Attribute $attribute
     * @return JsonResponse
     */
    final public function update(UpdateAttributeRequest $request, Attribute $attribute): JsonResponse
    {
        $attribute_data = $request->all();
        $attribute->update($attribute_data);
        return response()->json(['msg'=>'Attribute Updated Successfully', 'cls' => 'success']);
    }

    /**
     * @ param Attribute $attribute
     * @return JsonResponse
     */
    public function destroy(Attribute $attribute) : JsonResponse
    {
        $attribute->delete();
        return response()->json(['msg'=>'Attribute Deleted Successfully', 'cls' => 'warning']);
    }
    /**
     * @return JsonResponse
     */
    final public function get_attribute_list():JsonResponse
    {
        $attributes = (new Attribute())->getAttributeIdAndName();
        return response()->json($attributes);
    }
}
