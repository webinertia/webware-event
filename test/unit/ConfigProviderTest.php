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

namespace WebwareTest\Event;

use Phly\EventDispatcher\EventDispatcher;
use Phly\EventDispatcher\ListenerProvider\ListenerProviderAggregate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Webware\Event\ConfigProvider;
use Webware\Event\Container\ListenerProviderAggregateFactory;
use Webware\Event\Http\Middleware\Container\EventDispatcherMiddlewareFactory;
use Webware\Event\Http\Middleware\EventDispatcherMiddleware;

#[CoversClass(ConfigProvider::class)]
#[CoversMethod(ConfigProvider::class, '__invoke')]
#[CoversMethod(ConfigProvider::class, 'getDependencies')]
final class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    #[Test]
    public function getDependenciesReturnsCorrectAliases(): void
    {
        $deps = $this->provider->getDependencies();

        static::assertArrayHasKey('aliases', $deps);
        static::assertSame(EventDispatcher::class, $deps['aliases'][EventDispatcherInterface::class]);
        static::assertSame(ListenerProviderAggregate::class, $deps['aliases'][ListenerProviderInterface::class]);
    }

    #[Test]
    public function getDependenciesReturnsCorrectFactories(): void
    {
        $deps = $this->provider->getDependencies();

        static::assertArrayHasKey('factories', $deps);
        static::assertSame(
            ListenerProviderAggregateFactory::class,
            $deps['factories'][ListenerProviderAggregate::class],
        );
        static::assertSame(
            EventDispatcherMiddlewareFactory::class,
            $deps['factories'][EventDispatcherMiddleware::class],
        );
    }

    #[Test]
    public function invokeReturnsEmptyListenersAndProvidersByDefault(): void
    {
        $config = ($this->provider)();

        static::assertSame([], $config[ConfigProvider::LISTENER_KEY]);
        static::assertSame([], $config[ConfigProvider::LISTENER_PROVIDER_KEY]);
    }

    #[Test]
    public function invokeReturnsExpectedKeys(): void
    {
        $config = ($this->provider)();

        static::assertArrayHasKey('dependencies', $config);
        static::assertArrayHasKey(ConfigProvider::LISTENER_KEY, $config);
        static::assertArrayHasKey(ConfigProvider::LISTENER_PROVIDER_KEY, $config);
    }

    protected function setUp(): void
    {
        $this->provider = new ConfigProvider();
    }
}
