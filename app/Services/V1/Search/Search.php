<?php

namespace App\Services\V1\Search;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection find(string $table, string $search);
 * @method static Collection filter(string $table, array $items);
 */
class Search extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'search';
    }
}
