<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class Area extends Model
{
    use HasFactory;
    protected $guarded = [];

     /**
     * @param int $district_id
     * @return Builder|Collection
     */
    final public function getAreaByDistrictId(int $district_id):Builder|Collection
    {
        return self::query()->select('id', 'name')->where('district_id', $district_id)->get();
    }

    /**
     * @param int $division_id
     * @return Builder|Collection
     */
    final public function getAreaByDivisionId(int $division_id):Builder|Collection
    {
        return self::query()
            ->select('areas.id', 'areas.name')
            ->join('districts', 'areas.district_id', '=', 'districts.id')
            ->where('districts.division_id', $division_id)
            ->groupBy('areas.id', 'areas.name')
            ->get();
    }
}
