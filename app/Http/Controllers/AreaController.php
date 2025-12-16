<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreAreaRequest;
use App\Http\Requests\UpdateAreaRequest;

class AreaController extends Controller
{
    /**
     * @param int $division_key
     * @return JsonResponse
     */
    final public function index(int $division_key):JsonResponse
    {
        $areas = (new Area())->getAreaByDivisionId($division_key);
        $formatted = $areas->unique('id')->map(function ($area) {
            return [
                'area_id' => $area->id,
                'area_name' => $area->name,
            ];
        })->values();
        return response()->json($formatted);
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
    public function store(StoreAreaRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Area $area)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Area $area)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAreaRequest $request, Area $area)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Area $area)
    {
        //
    }
}
