<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Webware Event package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webware\Event\Container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webware\Event\EventDispatcherAwareInterface;

final class EventDispatcherAwareDelegator
{
    /**
     * @param callable(): object $callback
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(
        ContainerInterface $container,
        string $_requestedName,
        callable $callback,
    ): object {
        $serviceInstance = $callback();
        if ($serviceInstance instanceof EventDispatcherAwareInterface) {
            $eventDispatcher = $container->get(EventDispatcherInterface::class);
            $serviceInstance->setEventDispatcher($eventDispatcher);
        }

        return $serviceInstance;
    }
}
