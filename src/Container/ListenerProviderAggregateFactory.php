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

use Phly\EventDispatcher\LazyListener;
use Phly\EventDispatcher\ListenerProvider\ListenerProviderAggregate;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

use function is_array;
use function is_callable;
use function is_string;

/**
 * @internal
 */
final class ListenerProviderAggregateFactory
{
    public function __invoke(ContainerInterface $container): ListenerProviderAggregate
    {
        $listeners           = Configuration::getListeners($container, self::class);
        $listenerProviders   = Configuration::getListenerProviders($container, self::class);
        $prioritizedProvider = Configuration::getPrioritizedListenerProvider($container);
        $attachableProvider  = Configuration::getAttachableListenerProvider($container);
        $aggregate           = new ListenerProviderAggregate();

        foreach ($listeners as $eventType => $spec) {
            foreach ($spec as $listener) {
                if (is_string($listener)) {
                    if ($container->has($listener)) {
                        $attachableProvider->listen($eventType, new LazyListener($container, $listener));
                    } elseif (is_callable($listener)) {
                        $attachableProvider->listen($eventType, $listener);
                    }

                    continue;
                }

                if (is_array($listener) && isset($listener['listener'])) {
                    $listenerService = $listener['listener'];
                    if (is_string($listenerService) && $container->has($listenerService)) {
                        $resolvedListener = new LazyListener($container, $listenerService);
                    } elseif (is_callable($listenerService)) {
                        $resolvedListener = $listenerService;
                    } else {
                        continue;
                    }

                    if (isset($listener['priority'])) {
                        $prioritizedProvider->listen($eventType, $resolvedListener, $listener['priority']);
                    } else {
                        $attachableProvider->listen($eventType, $resolvedListener);
                    }

                    continue;
                }
            }
        }

        foreach ($listenerProviders as $provider) {
            $providerInstance = $container->has($provider) ? $container->get($provider) : null;
            if ($providerInstance instanceof ListenerProviderInterface) {
                $aggregate->attach($providerInstance);
            }
        }

        $aggregate->attach($prioritizedProvider);
        $aggregate->attach($attachableProvider);

        return $aggregate;
    }
}
