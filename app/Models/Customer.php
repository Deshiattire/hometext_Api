<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'email', 'phone','user_id'];

    public function store($input)
    {
        $customer = $this->prepareData($input);
        return self::query()->create($customer);
    }

    private function prepareData($input)
    {
        return $customer_data = [
            'name' => $input['name'] ?? '',
            'email' => $input['email'] ?? '',
            'phone' => $input['phone'] ?? '',
        ];
    }

    public function getCustomerBySearch($search)
    {
        $searchTerm = $search['search'] ?? '';
        return self::query()
        ->select('id', 'name', 'phone')
        ->where('name', 'like', '%' . $searchTerm . '%' )
        ->orWhere('phone', 'like', '%' . $searchTerm . '%' )
        ->take(15)
        ->get();
    }
}
