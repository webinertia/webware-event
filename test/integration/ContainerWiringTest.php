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

namespace WebwareTestIntegration\Event;

use Laminas\ServiceManager\ServiceManager;
use Phly\EventDispatcher\ConfigProvider as DispatcherConfigProvider;
use Phly\EventDispatcher\EventDispatcher;
use Phly\EventDispatcher\ListenerProvider\ListenerProviderAggregate;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Webware\Event\ConfigProvider;
use Webware\Event\Container\ListenerProviderAggregateFactory;
use Webware\Event\Event;
use WebwareTestIntegration\Event\Asset\RecordingListener;
use WebwareTestIntegration\Event\Asset\RecordingListenerProvider;

/**
 * Builds a container the way an application does — this package's provider
 * aggregated with phly's, which is what supplies the dispatcher — then uses the
 * documented config keys for real.
 */
#[CoversClass(ListenerProviderAggregateFactory::class)]
#[CoversMethod(ListenerProviderAggregateFactory::class, '__invoke')]
final class ContainerWiringTest extends TestCase
{
    public function testDispatcherResolvesToPhlyDispatcher(): void
    {
        $container = $this->createContainer();

        self::assertInstanceOf(EventDispatcher::class, $container->get(EventDispatcherInterface::class));
        self::assertInstanceOf(ListenerProviderAggregate::class, $container->get(ListenerProviderInterface::class));
    }

    public function testListenerDeclaredAsClassStringReceivesDispatchedEvent(): void
    {
        $listener  = new RecordingListener();
        $container = $this->createContainer(
            listeners: [Event::class => [RecordingListener::class]],
            services: [RecordingListener::class => $listener],
        );

        $container->get(EventDispatcherInterface::class)->dispatch(new Event());

        self::assertSame([Event::class], $listener->handled);
    }

    public function testListenerDeclaredWithCallableListenerReceivesDispatchedEvent(): void
    {
        $handled   = [];
        $container = $this->createContainer([
            Event::class => [
                [
                    'listener' => static function (Event $event) use (&$handled): void {
                        $handled[] = $event->getName();
                    },
                ],
            ],
        ]);

        $container->get(EventDispatcherInterface::class)->dispatch(new Event());

        self::assertSame([Event::class], $handled);
    }

    public function testListenerProviderDeclaredInConfigIsAttachedToAggregate(): void
    {
        $provider  = new RecordingListenerProvider();
        $container = $this->createContainer(
            listenerProviders: [RecordingListenerProvider::class],
            services: [RecordingListenerProvider::class => $provider],
        );

        $container->get(EventDispatcherInterface::class)->dispatch(new Event());

        self::assertSame([Event::class], $provider->handled);
    }

    /**
     * @param array<class-string, array<int, callable|string|array{listener: callable|string, priority?: int}>> $listeners
     * @param list<class-string>                                                                                 $listenerProviders
     */
    private function createContainer(
        array $listeners = [],
        array $listenerProviders = [],
        array $services = [],
    ): ServiceManager {
        $webware    = new ConfigProvider();
        $dispatcher = new DispatcherConfigProvider();
        $deps       = $webware->getDependencies();
        $phlyDeps   = $dispatcher->getDependencies();

        $config                                        = $webware();
        $config[ConfigProvider::LISTENER_KEY]          = $listeners;
        $config[ConfigProvider::LISTENER_PROVIDER_KEY] = $listenerProviders;

        return new ServiceManager([
            'aliases'    => $deps['aliases'],
            'factories'  => $deps['factories'] + $phlyDeps['factories'],
            'invokables' => $phlyDeps['invokables'],
            'services'   => ['config' => $config] + $services,
        ]);
    }
}
