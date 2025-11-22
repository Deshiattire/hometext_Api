<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Country extends Model
{
    use HasFactory;
    protected $guarded = [];
    
    protected $fillable = ['name', 'code', 'status'];

    public const STATUS_ACTIVE = 1;

    /**
     * @return Collection
     */
    final public function getCountryIdAndName():Collection
    {
        return self::query()->select('id', 'name')->where('status', self::STATUS_ACTIVE)->orderBy('name', 'asc')->get();
    }
}
