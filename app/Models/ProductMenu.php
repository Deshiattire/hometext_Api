<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductMenu extends Model
{
    use HasFactory;
    protected $fillable = [
        'menu_type',
        'name',
        'image',
        'parent_id',
        'child_id',
        'link'
    ];

    // protected $casts = [
    //     'link' => 'json'
    // ];
}
