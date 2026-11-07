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

use ArrayObject;
use Closure;
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
use Webware\Event\ListenerProvider\PositionableListenerProvider;
use WebwareTestIntegration\Event\Asset\RecordingListener;
use WebwareTestIntegration\Event\Asset\RecordingListenerProvider;

/**
 * Builds a container the way an application does - this package's provider
 * aggregated with phly's, which is what supplies the dispatcher - then uses the
 * documented config keys for real.
 */
#[CoversClass(InvalidListenerConfigurationException::class)]
#[CoversClass(ListenerProviderAggregateFactory::class)]
#[CoversClass(PositionableListenerProvider::class)]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forConflictingOrdering')]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forConflictingRelation')]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forCyclicOrdering')]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forInvalidEntry')]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forInvalidProviderService')]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forUnresolvableListenerReference')]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forUnresolvableProviderService')]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forUnresolvableService')]
#[CoversMethod(ListenerProviderAggregateFactory::class, '__invoke')]
#[CoversMethod(PositionableListenerProvider::class, 'getListenersForEvent')]
final class ContainerWiringTest extends TestCase
{
    /**
     * @param list<string> $order
     */
    private static function recorder(array &$order, string $label): Closure
    {
        return static function () use (&$order, $label): void {
            $order[] = $label;
        };
    }

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
    public function beforeAndAfterTogetherAreRejected(): void
    {
        $container = $this->createContainer(listeners: [
            Event::class => [
                ['listener' => 'listener.a', 'before' => 'listener.b', 'after' => 'listener.c'],
            ],
        ]);

        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage(
            'A listener for event "Webware\Event\Event" declares both "before" and "after";'
                . ' use one or the other.',
        );

        $container->get(EventDispatcherInterface::class);
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
    public function cyclicListenerOrderingIsRejected(): void
    {
        $order     = [];
        $container = $this->createContainer(
            listeners: [
                Event::class => [
                    ['listener' => 'listener.a', 'after' => 'listener.b'],
                    ['listener' => 'listener.b', 'after' => 'listener.a'],
                ],
            ],
            services: [
                'listener.a' => self::recorder($order, 'a'),
                'listener.b' => self::recorder($order, 'b'),
            ],
        );

        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage(
            'Listener ordering for event "Webware\Event\Event"'
                . ' contains a cycle: listener.b -> listener.a -> listener.b.',
        );

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
    public function duplicatedListenerEntryFiresTwice(): void
    {
        $listener  = new RecordingListener();
        $container = $this->createContainer(
            listeners: [Event::class => [RecordingListener::class, RecordingListener::class]],
            services: [RecordingListener::class => $listener],
        );

        $container->get(EventDispatcherInterface::class)->dispatch(new Event());

        static::assertSame([Event::class, Event::class], $listener->handled);
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
    public function listenerProviderThatIsNotAListenerProviderIsRejected(): void
    {
        $container = $this->createContainer(
            listenerProviders: [ArrayObject::class],
            services: [ArrayObject::class => new ArrayObject()],
        );

        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage(
            'Listener provider "ArrayObject" must implement Psr\EventDispatcher\ListenerProviderInterface;'
                . ' received ArrayObject.',
        );

        $container->get(EventDispatcherInterface::class);
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
    public function priorityCombinedWithRelativePlacementIsRejected(): void
    {
        $container = $this->createContainer(listeners: [
            Event::class => [
                ['listener' => 'listener.a', 'priority' => 5, 'after' => 'listener.b'],
            ],
        ]);

        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage(
            'A listener for event "Webware\Event\Event" declares "priority" together with'
                . ' "before" or "after"; priority and relative placement cannot be combined.',
        );

        $container->get(EventDispatcherInterface::class);
    }

    #[Test]
    public function relativePlacementComposesWithPriorityInOneProvider(): void
    {
        $order     = [];
        $container = $this->createContainer(
            listeners: [
                Event::class => [
                    'listener.anchor',
                    ['listener' => 'listener.after', 'after' => 'listener.anchor'],
                    ['listener' => 'listener.top', 'priority' => 50],
                    'listener.tail',
                ],
            ],
            services: [
                'listener.anchor' => self::recorder($order, 'anchor'),
                'listener.after'  => self::recorder($order, 'after'),
                'listener.tail'   => self::recorder($order, 'tail'),
                'listener.top'    => self::recorder($order, 'top'),
            ],
        );

        $container->get(EventDispatcherInterface::class)->dispatch(new Event());

        static::assertSame(['top', 'anchor', 'after', 'tail'], $order);
    }

    #[Test]
    public function relativePlacementOrdersListenersAroundTheirAnchor(): void
    {
        $order     = [];
        $container = $this->createContainer(
            listeners: [
                Event::class => [
                    'listener.anchor',
                    ['listener' => 'listener.after', 'after' => 'listener.anchor'],
                    ['listener' => 'listener.before', 'before' => 'listener.anchor'],
                ],
            ],
            services: [
                'listener.after'  => self::recorder($order, 'after'),
                'listener.anchor' => self::recorder($order, 'anchor'),
                'listener.before' => self::recorder($order, 'before'),
            ],
        );

        $container->get(EventDispatcherInterface::class)->dispatch(new Event());

        static::assertSame(['before', 'anchor', 'after'], $order);
    }

    #[Test]
    public function relativePlacementResolvesAForwardReference(): void
    {
        $order     = [];
        $container = $this->createContainer(
            listeners: [
                Event::class => [
                    ['listener' => 'listener.after', 'after' => 'listener.anchor'],
                    'listener.anchor',
                ],
            ],
            services: [
                'listener.after'  => self::recorder($order, 'after'),
                'listener.anchor' => self::recorder($order, 'anchor'),
            ],
        );

        $container->get(EventDispatcherInterface::class)->dispatch(new Event());

        static::assertSame(['anchor', 'after'], $order);
    }

    #[Test]
    public function unprioritizedListenerRunsAfterEveryPrioritizedListener(): void
    {
        $order     = [];
        $container = $this->createContainer([
            Event::class => [
                [
                    'listener' => static function (Event $event) use (&$order): void {
                        $order[] = 'unprioritized';
                    },
                ],
                [
                    'listener' => static function (Event $event) use (&$order): void {
                        $order[] = 'negative priority';
                    },
                    'priority' => -100,
                ],
            ],
        ]);

        $container->get(EventDispatcherInterface::class)->dispatch(new Event());

        static::assertSame(['negative priority', 'unprioritized'], $order);
    }

    #[Test]
    public function unregisteredListenerProviderServiceIsRejected(): void
    {
        $container = $this->createContainer(listenerProviders: ['not.registered.Provider']);

        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage(
            'Listener provider "not.registered.Provider" is not registered in the container.',
        );

        $container->get(EventDispatcherInterface::class);
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

    #[Test]
    public function unresolvableListenerReferenceIsRejectedWhenTheContainerIsBuilt(): void
    {
        $order     = [];
        $container = $this->createContainer(
            listeners: [
                Event::class => [
                    ['listener' => 'listener.after', 'after' => 'listener.missing'],
                ],
            ],
            services: ['listener.after' => self::recorder($order, 'after')],
        );

        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage(
            'Listener reference "listener.missing" for event "Webware\Event\Event"'
                . ' does not name a listener registered for that event.',
        );

        $container->get(EventDispatcherInterface::class);
    }

    /**
     * @param array<class-string, array<int, array{listener?: callable|string, priority?: int, before?: string, after?: string}|callable|string>> $listeners
     * @param list<class-string>                                                                                                          $listenerProviders
     * @param array<string, mixed>                                                                                                        $services
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
