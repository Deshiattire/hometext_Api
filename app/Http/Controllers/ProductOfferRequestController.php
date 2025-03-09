<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\ProductOfferRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProductOfferRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($type = null)
    {
        $data = null;
        switch ($type){
            case 'restock':
                $data = ProductOfferRequest::where('type', 'restock-request')->get();
                break;
            case 'offer':
                $data = ProductOfferRequest::where('type','make-an-offer')->get();
                break;
            default:
            $data = ProductOfferRequest::get();
        }
        return AppHelper::ResponseFormat(true, "Data found successfully.", $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function RestockRequest(Request $request)
    {
        try{
            $validator = Validator::make(
                $request->only('priduct_id', 'quentity'),
                [
                    'priduct_id' => 'required|int',
                    'quentity' => 'required|int',
                ]
            );
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation_err',
                    'error' => $validator->errors()
                ],
                400);
            }
    
            $restock = ProductOfferRequest::create([
                'priduct_id'    => $request->priduct_id,
                'quentity'      => $request->quentity,
                'user_id'       => Auth::id(),
                'type'          => "restock-request"
            ]);
    
            return AppHelper::ResponseFormat(true, 'Restock request sent successfully.', $restock);

        }catch(\Exception $e){
            info("RestockRequest_FAILED", ['data' => $request->all(), 'error' => $e->getMessage()]);
            return AppHelper::ResponseFormat(false, "Restock request sent faild." );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function MakeAnOffer(Request $request)
    {
        try{
            $validator = Validator::make(
                $request->only('priduct_id', 'amount'),
                [
                    'priduct_id' => 'required|int',
                    'amount' => 'required|int',
                ]
            );
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation_err',
                    'error' => $validator->errors()
                ],
                400);
            }

            $offer = ProductOfferRequest::create([
                'priduct_id'    => $request->priduct_id,
                'amount'      => $request->amount,
                'user_id'       => Auth::id(),
                'type'          => "make-an-offer"
            ]);
    
            return AppHelper::ResponseFormat(true, 'Make an offer sent successfully.', $offer);

        }catch(\Exception $e){
            info("Make-an-offer_FAILED", ['data' => $request->all(), 'error' => $e->getMessage()]);
            return AppHelper::ResponseFormat(false, "Make an offer sent faild." );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductOfferRequest $productOfferRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductOfferRequest $productOfferRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductOfferRequest $productOfferRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductOfferRequest $productOfferRequest)
    {
        //
    }
}
