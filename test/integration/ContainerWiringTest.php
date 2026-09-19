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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Webware\Event\ConfigProvider;
use Webware\Event\Container\ListenerProviderAggregateFactory;
use Webware\Event\Event;
use Webware\Event\Exception\InvalidListenerConfigurationException;
use WebwareTestIntegration\Event\Asset\RecordingListener;
use WebwareTestIntegration\Event\Asset\RecordingListenerProvider;

/**
 * Builds a container the way an application does — this package's provider
 * aggregated with phly's, which is what supplies the dispatcher — then uses the
 * documented config keys for real.
 */
#[CoversClass(InvalidListenerConfigurationException::class)]
#[CoversClass(ListenerProviderAggregateFactory::class)]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forInvalidEntry')]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forUnresolvableService')]
#[CoversMethod(ListenerProviderAggregateFactory::class, '__invoke')]
final class ContainerWiringTest extends TestCase
{
    #[Test]
    public function arrayListenerWithoutPriorityIsRegisteredExactlyOnce(): void
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

        static::assertCount(1, $handled);
    }

    #[Test]
    public function callableStringIsNotAcceptedAsAListener(): void
    {
        $container = $this->createContainer(listeners: [Event::class => ['strtolower']]);

        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage('Listener service "strtolower" is not registered in the container.');

        $container->get(EventDispatcherInterface::class);
    }

    #[Test]
    public function dispatcherResolvesToPhlyDispatcher(): void
    {
        $container = $this->createContainer();

        static::assertInstanceOf(EventDispatcher::class, $container->get(EventDispatcherInterface::class));
        static::assertInstanceOf(ListenerProviderAggregate::class, $container->get(ListenerProviderInterface::class));
    }

    #[Test]
    public function everyListenerForAnEventIsRegisteredRegardlessOfDeclaredForm(): void
    {
        $handled   = [];
        $listener  = new RecordingListener();
        $container = $this->createContainer(
            listeners: [
                Event::class => [
                    [
                        'listener' => static function (Event $event) use (&$handled): void {
                            $handled[] = $event->getName();
                        },
                    ],
                    RecordingListener::class,
                    [
                        'listener' => static function (Event $event) use (&$handled): void {
                            $handled[] = $event->getName();
                        },
                        'priority' => 5,
                    ],
                ],
            ],
            services: [RecordingListener::class => $listener],
        );

        $container->get(EventDispatcherInterface::class)->dispatch(new Event());

        static::assertSame([Event::class], $listener->handled);
        static::assertCount(2, $handled);
    }

    #[Test]
    public function invalidListenerEntryTypeIsRejected(): void
    {
        $container = $this->createContainer(listeners: [Event::class => [['priority' => 5]]]);

        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage('A listener entry must be a container service id or a callable; received null.');

        $container->get(EventDispatcherInterface::class);
    }

    #[Test]
    public function listenerDeclaredAsClassStringReceivesDispatchedEvent(): void
    {
        $listener  = new RecordingListener();
        $container = $this->createContainer(
            listeners: [Event::class => [RecordingListener::class]],
            services: [RecordingListener::class => $listener],
        );

        $container->get(EventDispatcherInterface::class)->dispatch(new Event());

        static::assertSame([Event::class], $listener->handled);
    }

    #[Test]
    public function listenerDeclaredWithCallableListenerReceivesDispatchedEvent(): void
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

        static::assertSame([Event::class], $handled);
    }

    #[Test]
    public function listenerProviderDeclaredInConfigIsAttachedToAggregate(): void
    {
        $provider  = new RecordingListenerProvider();
        $container = $this->createContainer(
            listenerProviders: [RecordingListenerProvider::class],
            services: [RecordingListenerProvider::class => $provider],
        );

        $container->get(EventDispatcherInterface::class)->dispatch(new Event());

        static::assertSame([Event::class], $provider->handled);
    }

    #[Test]
    public function prioritizedListenerReceivesDispatchedEvent(): void
    {
        $handled   = [];
        $container = $this->createContainer([
            Event::class => [
                [
                    'listener' => static function (Event $event) use (&$handled): void {
                        $handled[] = $event->getName();
                    },
                    'priority' => 10,
                ],
            ],
        ]);

        $container->get(EventDispatcherInterface::class)->dispatch(new Event());

        static::assertSame([Event::class], $handled);
    }

    #[Test]
    public function unregisteredListenerServiceIsRejected(): void
    {
        $container = $this->createContainer(listeners: [Event::class => ['not.registered.Service']]);

        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage(
            'Listener service "not.registered.Service" is not registered in the container.',
        );

        $container->get(EventDispatcherInterface::class);
    }

    /**
     * @param array<class-string, array<int, array{listener?: callable|string, priority?: int}|callable|string>> $listeners
     * @param list<class-string>                                                                                  $listenerProviders
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
