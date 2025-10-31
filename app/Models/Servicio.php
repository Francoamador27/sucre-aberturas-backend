<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    protected $fillable = [
        'title',
        'description',
        'slug',
        'image',
        'mainImage',
        'tags',
        'features',
        'gallery',
        'category',
        'create_data'
    ];

    // Para castear los JSON automáticamente
    protected $casts = [
        'tags' => 'array',
        'features' => 'array',
        'gallery' => 'array',
    ];
}
