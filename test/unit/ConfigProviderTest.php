<?php

declare(strict_types=1);

namespace Webware\EventTest;

use Phly\EventDispatcher\EventDispatcher;
use Phly\EventDispatcher\ListenerProvider\ListenerProviderAggregate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Webware\Event\ConfigProvider;
use Webware\Event\Container\ListenerProviderAggregateFactory;
use Webware\Event\Middleware\EventDispatcherMiddleware;
use Webware\Event\Middleware\EventDispatcherMiddlewareFactory;

#[CoversClass(ConfigProvider::class)]
#[CoversMethod(ConfigProvider::class, '__invoke')]
#[CoversMethod(ConfigProvider::class, 'getDependencies')]
final class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new ConfigProvider();
    }

    public function testInvokeReturnsExpectedKeys(): void
    {
        $config = ($this->provider)();

        self::assertIsArray($config);
        self::assertArrayHasKey('dependencies', $config);
        self::assertArrayHasKey(ConfigProvider::LISTENER_KEY, $config);
        self::assertArrayHasKey(ConfigProvider::LISTENER_PROVIDER_KEY, $config);
    }

    public function testInvokeReturnsEmptyListenersAndProvidersByDefault(): void
    {
        $config = ($this->provider)();

        self::assertSame([], $config[ConfigProvider::LISTENER_KEY]);
        self::assertSame([], $config[ConfigProvider::LISTENER_PROVIDER_KEY]);
    }

    public function testGetDependenciesReturnsCorrectAliases(): void
    {
        $deps = $this->provider->getDependencies();

        self::assertArrayHasKey('aliases', $deps);
        self::assertSame(EventDispatcher::class, $deps['aliases'][EventDispatcherInterface::class]);
        self::assertSame(ListenerProviderAggregate::class, $deps['aliases'][ListenerProviderInterface::class]);
    }

    public function testGetDependenciesReturnsCorrectFactories(): void
    {
        $deps = $this->provider->getDependencies();

        self::assertArrayHasKey('factories', $deps);
        self::assertSame(
            ListenerProviderAggregateFactory::class,
            $deps['factories'][ListenerProviderAggregate::class],
        );
        self::assertSame(
            EventDispatcherMiddlewareFactory::class,
            $deps['factories'][EventDispatcherMiddleware::class],
        );
    }
}
