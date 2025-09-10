<?php

namespace Roberts\LaravelSingledbTenancy\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Roberts\LaravelSingledbTenancy\LaravelSingledbTenancy
 */
class LaravelSingledbTenancy extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Roberts\LaravelSingledbTenancy\LaravelSingledbTenancy::class;
    }
}
