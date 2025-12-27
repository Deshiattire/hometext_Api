<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status', 'user_id'];

    public const STATUS_ACTIVE =1;

    /**
     * @return LengthAwarePaginator
     */
    final public function getAttributeList(): LengthAwarePaginator
    {
        return self::query()->with(['user', 'value', 'value.user:id,first_name,last_name'])->orderBy('updated_at', 'desc')->paginate(50);
    }
    /**
     * @return BelongsTo
     */
    final public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    /**
     * @return HasMany
     */
    final public function value():HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }
    /**
     * @return Builder[]|Collection
     */
    final public function getAttributeIdAndName():Builder|Collection
    {
        return self::query()
        ->select('id', 'name')
        ->with('value:id,name,attribute_id')
        ->where('status', self::STATUS_ACTIVE)
        ->get();
    }
}
