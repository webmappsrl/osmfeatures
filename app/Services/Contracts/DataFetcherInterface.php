<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Model;

interface DataFetcherInterface
{
    public function fetchData(string $tags): ?array;
}
