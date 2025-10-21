<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pokemon extends Model
{
    protected $fillable = [
        'name',
        'type',
        'image',
        'pokemon_id',
        'url'
    ];

    protected $casts = [
        'type' => 'array',
    ];
}
