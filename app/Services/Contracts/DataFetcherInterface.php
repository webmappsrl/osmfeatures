<?php

namespace App\Services\Contracts;

use Illuminate\Database\Eloquent\Model;

interface DataFetcherInterface
{
    public function fetchData(array $tags): ?array;
}
