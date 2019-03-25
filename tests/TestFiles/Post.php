<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $casts = [
        'images' => 'json',
        'files' => 'json'
    ];
}
