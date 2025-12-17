<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payment_status = 'Unpaid';
        if($this->payment_status === Order::PAID){
            $payment_status = 'Paid';
        }elseif ($this->payment_status === Order::PARTIAL_PAID){
            $payment_status = 'Partially Paid';
        }elseif ($this->payment_status === null || $this->payment_status === '' || $this->payment_status === 0){
            $payment_status = 'Unpaid';
        }
        return [
            'id'=>$this->id,
            'created_at'=>$this->created_at ? $this->created_at->toDayDateTimeString() : '',
            'updated_at'=> $this->updated_at && $this->created_at != $this->updated_at ? $this->updated_at->toDayDateTimeString(): 'Not Updated',
            'customer'=>new CustomerDetailsResource($this->customer),
            'order_number'=>$this->order_number,
            'order_status'=>$this->order_status,
            'order_status_string'=>$this->order_status == Order::STATUS_COMPLETED ? 'Completed' :'Pending',
            'payment_method'=>new PaymentMethodDetailsResource($this->payment_method),
            'payment_status'=>$payment_status,
            'sales_manager'=>new SalesManagerListResource($this->sales_manager),
            'shop'=>new ShopListResource($this->shop),
            'discount'=>$this->discount,
            'due_amount'=>$this->due_amount,
            'paid_amount'=>$this->paid_amount,
            'quantity'=>$this->quantity,
            'sub_total'=>$this->sub_total,
            'total'=>$this->total,
            'order_details'=> OrderDetailsListResource::collection($this->order_details),
            'transactions'=> TransactionListResource::collection($this->transactions),
        ];
    }
}
