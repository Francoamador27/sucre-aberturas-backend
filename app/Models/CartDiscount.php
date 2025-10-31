<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'condition_type',
        'discount_value',
        'min_value',
        'max_discount',
        'usage_limit',
        'usage_count',
        'is_active',
        'start_date',
        'end_date',
    ];
}
