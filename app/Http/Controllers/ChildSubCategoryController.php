<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChildSubCategoryEditResource;
use App\Http\Resources\ChildSubCategoryListResource;
use App\Manager\ImageUploadManager;
use App\Models\ChildSubCategory;
use App\Http\Requests\StoreChildSubCategoryRequest;
use App\Http\Requests\UpdateChildSubCategoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChildSubCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $subCategories = (new ChildSubCategory())->getAllChildSubCategories($request->all());
        return ChildSubCategoryListResource::collection($subCategories);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * @param StoreChildSubCategoryRequest $request
     * @return JsonResponse
     */
//    public function store(StoreChildSubCategoryRequest $request):JsonResponse
//    {
//        $child_sub_category = $request->except('photo');
//        $child_sub_category['slug'] = Str::slug($request->input('slug'));
////        $child_sub_category['user_id'] = auth()->id;
//        // Set user_id to 1
//        $child_sub_category['user_id'] = 1;
//        if($request->has('photo')){
//            $child_sub_category['photo'] = $this->processImageUpload($request->input('photo'), $child_sub_category['slug']);
//        }
//        (new ChildSubCategory())->storeChildSubCategory($child_sub_category);
//        return response()->json(['msg'=>'Child Sub Category Created Successfully', 'cls' => 'success']);
//
//    }
    public function store(StoreChildSubCategoryRequest $request): JsonResponse
    {
        $child_sub_category = $request->except('photo');
        $child_sub_category['slug'] = Str::slug($request->input('slug'));
        // Set user_id to 1
        $child_sub_category['user_id'] = 1;

        if ($request->has('photo')) {
            $child_sub_category['photo'] = $this->processImageUpload(
                $request->file('photo'), // Use file() instead of input()
                $child_sub_category['slug']
            );
        }

        (new ChildSubCategory())->storeChildSubCategory($child_sub_category);

        return response()->json(['msg' => 'Child Sub Category Created Successfully', 'cls' => 'success']);
    }


    /**
     * @param ChildSubCategory $childSubCategory
     * @return ChildSubCategoryEditResource
     */
    /**
     * @param ChildSubCategory $childSubCategory
     * @return ChildSubCategoryEditResource
     */
    public function show(ChildSubCategory $childSubCategory):ChildSubCategoryEditResource
    {
        return new ChildSubCategoryEditResource($childSubCategory);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ChildSubCategory $childSubCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateChildSubCategoryRequest $request, ChildSubCategory $childSubCategory)
    {
        $child_sub_category_data = $request->except('photo');
        $child_sub_category_data['slug'] = Str::slug($request->input('slug'));
        if($request->has('photo')){
            $child_sub_category_data['photo']  = $this->processImageUpload($request->input('photo'),  $child_sub_category_data['slug'], $childSubCategory->photo);
        }
        $childSubCategory->update($child_sub_category_data);
        return response()->json(['msg'=>'SUb Category Updated Successfully', 'cls' => 'success']);
    }

    /**
     * @param ChildSubCategory $childSubCategory
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(ChildSubCategory $childSubCategory):JsonResponse
    {
        if(!empty($childCubCategory->photo)){
            ImageUploadManager::deletePhoto(ChildSubCategory::IMAGE_UPLOAD_PATH, $childSubCategory->photo);
            ImageUploadManager::deletePhoto(ChildSubCategory::THUMB_IMAGE_UPLOAD_PATH, $childSubCategory->photo);
        }
        $childSubCategory->delete();
        return response()->json(['msg'=>'Child Sub Category Deleted Successfully', 'cls' => 'warning']);
    }
    /**
     * @param string $file
     * @param string $name
     * @param string|null $existing_photo
     * @return string
     */
    private function processImageUpload(string $file, string $name, string|null $existing_photo = null):string
    {
        $width = 800;
        $height = 800;
        $width_thumb = 150;
        $height_thumb = 150;
        $path = ChildSubCategory::IMAGE_UPLOAD_PATH;
        $path_thumb = ChildSubCategory::THUMB_IMAGE_UPLOAD_PATH;

        if(!empty($existing_photo)){
            ImageUploadManager::deletePhoto(ChildSubCategory::IMAGE_UPLOAD_PATH, $existing_photo);
            ImageUploadManager::deletePhoto(ChildSubCategory::THUMB_IMAGE_UPLOAD_PATH, $existing_photo);
        }

        $photo_name= ImageUploadManager::uploadImage($name, $width, $height, $path, $file);
        ImageUploadManager::uploadImage($name, $width_thumb, $height_thumb, $path_thumb, $file);
        return $photo_name;
    }
    /**
     * @param int $category_id
     * @return JsonResponse
     */
    final public function get_child_sub_category_list(int $sub_category_id):JsonResponse
    {
        $childSubCategories = (new ChildSubCategory())->getChildSubCategoryIdAndName($sub_category_id);
        return response()->json($childSubCategories);
    }
}
