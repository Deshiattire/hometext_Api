<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeValue extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'attribute_id', 'status', 'user_id'];

    /**
     * @return BelongsTo
     */
    final public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * @return BelongsTo
     */
    final public function attribute():BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
