<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Event package.
 *
 * Copyright (c) 2026 Joey (aka Tyrsson) Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webware\Event\Container;

use Psr\Container\ContainerInterface;
use Webware\Event\EventDispatcherAwareInterface;

final class EventDispatcherAwareDelegator
{
    /**
     * @param callable(): object $callback
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        callable $callback,
    ): object {
        $serviceInstance = $callback();
        if ($serviceInstance instanceof EventDispatcherAwareInterface) {
            $eventDispatcher = Configuration::getEventDispatcher($container);
            $serviceInstance->setEventDispatcher($eventDispatcher);
        }

        return $serviceInstance;
    }
}
