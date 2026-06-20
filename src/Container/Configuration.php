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

use Phly\EventDispatcher\ListenerProvider\AttachableListenerProvider;
use Phly\EventDispatcher\ListenerProvider\PrioritizedListenerProvider;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Webmozart\Assert\Assert;
use Webware\Core\Configuration as Config;
use Webware\Core\Exception\ContainerException;
use Webware\Event\ConfigProvider;

final readonly class Configuration extends Config
{
    public const string CONFIG_KEY = EventDispatcherInterface::class;

    public static function getEventDispatcher(ContainerInterface $container): EventDispatcherInterface
    {
        $dispatcher = $container->get(EventDispatcherInterface::class);
        Assert::isInstanceOf($dispatcher, EventDispatcherInterface::class);

        return $dispatcher;
    }

    /**
     * @return array<class-string, array<int, array{listener: callable|class-string, priority?: int}|class-string>>
     */
    public static function getListeners(ContainerInterface $container, string $callingFactory): array
    {
        if (! $container->has('config')) {
            throw ContainerException::forMissingConfigService('config', static::class);
        }
        $config = $container->get('config');
        Assert::isArray($config);

        /** @var array<class-string, array<int, array{listener: callable|class-string, priority?: int}|class-string>> $listeners */
        $listeners = $config[ConfigProvider::LISTENER_KEY] ?? [];

        return $listeners;
    }

    /**
     * @return array<class-string>
     */
    public static function getListenerProviders(ContainerInterface $container, string $callingFactory): array
    {
        if (! $container->has('config')) {
            throw ContainerException::forMissingConfigService('config', static::class);
        }
        $config = $container->get('config');
        Assert::isArray($config);

        /** @var array<class-string> $providers */
        $providers = $config[ConfigProvider::LISTENER_PROVIDER_KEY] ?? [];

        return $providers;
    }

    public static function getPrioritizedListenerProvider(ContainerInterface $container): PrioritizedListenerProvider
    {
        $provider = $container->get(PrioritizedListenerProvider::class);
        Assert::isInstanceOf($provider, PrioritizedListenerProvider::class);

        return $provider;
    }

    public static function getAttachableListenerProvider(ContainerInterface $container): AttachableListenerProvider
    {
        $provider = $container->get(AttachableListenerProvider::class);
        Assert::isInstanceOf($provider, AttachableListenerProvider::class);

        return $provider;
    }
}
