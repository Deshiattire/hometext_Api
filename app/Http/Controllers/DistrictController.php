<?php

namespace App\Http\Controllers;

use App\Models\District;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreDistrictRequest;
use App\Http\Requests\UpdateDistrictRequest;

class DistrictController extends Controller
{
     /**
     * @param int $division_id
     * @return JsonResponse
     */
    final public function index(int $division_id):JsonResponse
    {
        $districts = (new District())->getDistrictByDivisionId($division_id);
        $formatted = $districts->map(function ($district) {
            return [
                'district_id' => $district->id,
                'district_name' => $district->name,
            ];
        });
        return response()->json($formatted);
    }


}
