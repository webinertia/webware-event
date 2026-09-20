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
use Phly\EventDispatcher\ListenerProvider\AttachableListenerProvider;
use Phly\EventDispatcher\ListenerProvider\ListenerProviderAggregate;
use Phly\EventDispatcher\ListenerProvider\PrioritizedListenerProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Webware\Event\ConfigProvider;
use Webware\Event\Exception\InvalidListenerConfigurationException;

use function is_array;
use function is_callable;
use function is_string;

/**
 * @internal
 *
 * @import-type ConfigShape from ConfigProvider
 */
final class ListenerProviderAggregateFactory
{
    /**
     * Resolves a configured listener entry to a callable, deferring container lookups to `LazyListener`.
     *
     * A string entry is a container service id, never a callable string: ids are class-strings naming the
     * listener implementation. An id the container does not have is a configuration error rather than a
     * listener to skip: skipping hides the typo until the event that should have been handled silently
     * never happens.
     *
     * @throws InvalidListenerConfigurationException
     */
    private function resolveListener(ContainerInterface $container, mixed $service): callable
    {
        if (is_string($service)) {
            if ($container->has($service)) {
                return new LazyListener($container, $service);
            }

            throw InvalidListenerConfigurationException::forUnresolvableService($service);
        }

        if (is_callable($service)) {
            return $service;
        }

        throw InvalidListenerConfigurationException::forInvalidEntry($service);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ListenerProviderAggregate
    {
        /** @var ConfigShape $config */
        $config = $container->get('config');

        $listeners         = $config[ConfigProvider::LISTENER_KEY];
        $listenerProviders = $config[ConfigProvider::LISTENER_PROVIDER_KEY];

        $prioritizedProvider = $container->get(PrioritizedListenerProvider::class);
        $attachableProvider  = $container->get(AttachableListenerProvider::class);

        $aggregate = new ListenerProviderAggregate();

        foreach ($listeners as $eventType => $spec) {
            foreach ($spec as $listener) {
                if (! is_array($listener)) {
                    $attachableProvider->listen($eventType, $this->resolveListener($container, $listener));

                    continue;
                }

                $resolved = $this->resolveListener($container, $listener['listener'] ?? null);

                $priority = $listener['priority'] ?? null;

                if (null === $priority) {
                    $attachableProvider->listen($eventType, $resolved);

                    continue;
                }

                $prioritizedProvider->listen($eventType, $resolved, $priority);
            }
        }

        foreach ($listenerProviders as $provider) {
            if (! $container->has($provider)) {
                continue;
            }

            /** @var ListenerProviderInterface|null $providerInstance */
            $providerInstance = $container->get($provider);

            if ($providerInstance instanceof ListenerProviderInterface) {
                $aggregate->attach($providerInstance);
            }
        }

        $aggregate->attach($prioritizedProvider);
        $aggregate->attach($attachableProvider);

        return $aggregate;
    }
}
