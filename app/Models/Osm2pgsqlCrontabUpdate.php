<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Osm2pgsqlCrontabUpdate extends Model
{
    use HasFactory;

    protected $table = 'osm2pgsql_crontab_updates';

    protected $fillable = [
        'imported_at',
        'from_lua',
        'from_pbf',
        'success',
        'log',
    ];

    public $timestamps = false;
}
