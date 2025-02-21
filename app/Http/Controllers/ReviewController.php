<?php

namespace App\Http\Controllers;

use App\Models\ProductStarRating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\error;

class ReviewController extends Controller
{
    public function index(){
        $StarRating = ProductStarRating::get();

        if($StarRating != null){
            return response()->json([
                'messsage' => "Successfully data found",
                'data' => $StarRating
            ]);
        }else{
            return response()->json([
                'messsage' => "No data found",
                'data' => []
            ]);
        }
    }

    public function ProductWiseStarRating($productId){
        $StarRating = ProductStarRating::where('product_id', $productId)->get();

        if($StarRating != null){
            return response()->json([
                'messsage' => "Successfully data found",
                'data' => $StarRating
            ]);
        }else{
            return response()->json([
                'messsage' => "No data found",
                'data' => []
            ]);
        }
    }

    public function ProductUserWiseStarRating(){
        $StarRating = ProductStarRating::where('user_id', Auth::id())
                    ->with('product')
                    ->get();

        if($StarRating != null){
            return response()->json([
                'messsage' => "Successfully data found",
                'data' => $StarRating
            ]);
        }else{
            return response()->json([
                'messsage' => "No data found",
                'data' => []
            ]);
        }
    }

    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'product_id'=> 'required|int',
            'star_rating'=> 'required|int|max:5',
            'review'=> 'required|string|max:255'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'error' => [
                    'message' => 'Request failed',
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        try{
            $data = [
                'user_id'=> Auth::id(),
                'product_id'=> $request->product_id,
                'rating'=> $request->star_rating,
                'comment'=> $request->review
            ];

            $pro = ProductStarRating::create($data);

            return response()->json(['message' => 'Data inserted successfully']);
        }catch(\Exception $e){
            info("ReviewGenerate_FAILED", ['data' => $request->all(), 'error' => $e->getMessage()]);
            return response()->json(['message' => 'ReviewGenerate_FAILED']);
        }

    }
}
