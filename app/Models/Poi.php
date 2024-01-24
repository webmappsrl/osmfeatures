<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poi extends Model
{
    use HasFactory;

    protected $table = 'pois';

    protected $fillable = ['name', 'class', 'subclass', 'geom', 'updated_at'];
}
