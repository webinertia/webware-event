<?php

declare(strict_types=1);

namespace Webware\Event\Middleware;

use Psr\Container\ContainerInterface;
use Webware\Event\Container\Configuration as Config;

final class EventDispatcherMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): EventDispatcherMiddleware
    {
        return new EventDispatcherMiddleware(
            eventDispatcher: Config::getEventDispatcher($container),
        );
    }
}
