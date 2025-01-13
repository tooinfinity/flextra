<?php

declare(strict_types=1);

namespace TooInfinity\Flextra;

use Illuminate\Support\Facades\Facade;

final class FlextraFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'module-inertia-react';
    }
}
