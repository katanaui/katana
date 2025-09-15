<?php

namespace Katanaui;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Katanaui\Katana\Skeleton\SkeletonClass
 */
class KatanaFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'katana';
    }
}
