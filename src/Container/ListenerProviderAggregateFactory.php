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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Webware\Event\ConfigProvider;
use Webware\Event\Exception\InvalidListenerConfigurationException;
use Webware\Event\ListenerProvider\PositionableListenerProvider;

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
     * Accepts a resolved provider service, rejecting anything that is not a `ListenerProviderInterface`.
     *
     * A `listener_providers` entry that resolves to the wrong kind of object is a configuration error rather
     * than a provider to skip, for the same reason an unresolvable listener id is: skipping hides the mistake
     * until the event that should have been handled silently never happens.
     *
     * @throws InvalidListenerConfigurationException
     */
    private function assertListenerProvider(string $service, mixed $provider): ListenerProviderInterface
    {
        if ($provider instanceof ListenerProviderInterface) {
            return $provider;
        }

        throw InvalidListenerConfigurationException::forInvalidProviderService($service, $provider);
    }

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

        $aggregate = new ListenerProviderAggregate();
        $provider  = new PositionableListenerProvider();

        foreach ($listeners as $eventType => $spec) {
            foreach ($spec as $entry) {
                $declaration = ListenerDeclaration::fromSpec($eventType, $entry);

                $declaration->registerWith(
                    $provider,
                    $eventType,
                    $this->resolveListener($container, $declaration->service),
                );
            }
        }

        $provider->validateOrdering();

        foreach ($listenerProviders as $providerService) {
            if (! $container->has($providerService)) {
                throw InvalidListenerConfigurationException::forUnresolvableProviderService($providerService);
            }

            $aggregate->attach($this->assertListenerProvider($providerService, $container->get($providerService)));
        }

        $aggregate->attach($provider);

        return $aggregate;
    }
}
