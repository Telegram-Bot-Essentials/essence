<?php

namespace TelegramBotEssentials\Essence\Traits;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;

trait CanBuildDependencyInjectedClass
{
    /**
     * @throws BindingResolutionException
     */
    protected function buildDependencyInjectedClass(object|string $class): object
    {
        if (is_object($class)) {
            return $class;
        }

        return app(Container::class)->make($class);
    }
}
