<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Event package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebwareTest\Event\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Event\Container\ListenerDeclaration;
use Webware\Event\Event;
use Webware\Event\Exception\InvalidListenerConfigurationException;
use Webware\Event\ListenerProvider\PositionableListenerProvider;

#[CoversClass(ListenerDeclaration::class)]
#[CoversClass(PositionableListenerProvider::class)]
#[CoversMethod(ListenerDeclaration::class, 'fromArray')]
#[CoversMethod(ListenerDeclaration::class, 'fromSpec')]
#[CoversMethod(ListenerDeclaration::class, 'registerWith')]
final class ListenerDeclarationTest extends TestCase
{
    #[Test]
    public function aBareServiceIdIsItsOwnListenerType(): void
    {
        $declaration = ListenerDeclaration::fromSpec(Event::class, 'some.Listener');

        static::assertSame('some.Listener', $declaration->service);
        static::assertSame('some.Listener', $declaration->listenerType);
    }

    #[Test]
    public function aBeforeEntryRegistersAheadOfItsAnchor(): void
    {
        $provider = new PositionableListenerProvider();
        $anchor   = static function (): void {};
        $child    = static function (): void {};

        $provider->listen(Event::class, $anchor, null, 'Anchor');

        ListenerDeclaration::fromSpec(
            Event::class,
            ['listener' => 'child.Listener', 'before' => 'Anchor'],
        )->registerWith($provider, Event::class, $child);

        static::assertSame([$child, $anchor], $provider->getListenersForEvent(new Event()));
    }

    #[Test]
    public function aCallableEntryHasNoListenerType(): void
    {
        $listener    = static function (): void {};
        $declaration = ListenerDeclaration::fromSpec(Event::class, ['listener' => $listener]);

        static::assertSame($listener, $declaration->service);
        static::assertNull($declaration->listenerType);
    }

    #[Test]
    public function anAfterEntryRegistersBehindItsAnchor(): void
    {
        $provider = new PositionableListenerProvider();
        $anchor   = static function (): void {};
        $child    = static function (): void {};

        $provider->listen(Event::class, $anchor, null, 'Anchor');

        ListenerDeclaration::fromSpec(
            Event::class,
            ['listener' => 'child.Listener', 'after' => 'Anchor'],
        )->registerWith($provider, Event::class, $child);

        static::assertSame([$anchor, $child], $provider->getListenersForEvent(new Event()));
    }

    #[Test]
    public function aPlainEntryRegistersUnprioritizedAndUnpositioned(): void
    {
        $provider = new PositionableListenerProvider();
        $plain    = static function (): void {};
        $tail     = static function (): void {};

        ListenerDeclaration::fromSpec(Event::class, 'plain.Listener')
            ->registerWith($provider, Event::class, $plain);

        $provider->listen(Event::class, $tail);

        static::assertSame([$plain, $tail], $provider->getListenersForEvent(new Event()));
    }

    #[Test]
    public function aPriorityEntryRegistersAheadOfAnUnprioritizedEntry(): void
    {
        $provider    = new PositionableListenerProvider();
        $prioritized = static function (): void {};
        $plain       = static function (): void {};

        $provider->listen(Event::class, $plain);

        ListenerDeclaration::fromSpec(
            Event::class,
            ['listener' => 'prioritized.Listener', 'priority' => 5],
        )->registerWith($provider, Event::class, $prioritized);

        static::assertSame([$prioritized, $plain], $provider->getListenersForEvent(new Event()));
    }

    #[Test]
    public function aShapedArrayIsAcceptedDirectly(): void
    {
        $declaration = ListenerDeclaration::fromArray(
            Event::class,
            ['listener' => 'some.Listener', 'after' => 'Anchor'],
        );

        static::assertSame('some.Listener', $declaration->service);
        static::assertSame('some.Listener', $declaration->listenerType);
    }

    #[Test]
    public function aShapedArrayKeepsItsServiceAsTheListenerType(): void
    {
        $declaration = ListenerDeclaration::fromSpec(
            Event::class,
            ['listener' => 'some.Listener', 'priority' => 7],
        );

        static::assertSame('some.Listener', $declaration->service);
        static::assertSame('some.Listener', $declaration->listenerType);
    }

    #[Test]
    public function beforeAndAfterTogetherAreRejected(): void
    {
        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage(
            'A listener for event "Webware\Event\Event" declares both "before" and "after";'
                . ' use one or the other.',
        );

        ListenerDeclaration::fromSpec(
            Event::class,
            ['listener' => 'some.Listener', 'before' => 'A', 'after' => 'B'],
        );
    }

    #[Test]
    public function priorityCombinedWithAfterIsRejected(): void
    {
        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage(
            'A listener for event "Webware\Event\Event" declares "priority" together with'
                . ' "before" or "after"; priority and relative placement cannot be combined.',
        );

        ListenerDeclaration::fromSpec(
            Event::class,
            ['listener' => 'some.Listener', 'priority' => 5, 'after' => 'A'],
        );
    }

    #[Test]
    public function priorityCombinedWithBeforeIsRejected(): void
    {
        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage(
            'A listener for event "Webware\Event\Event" declares "priority" together with'
                . ' "before" or "after"; priority and relative placement cannot be combined.',
        );

        ListenerDeclaration::fromSpec(
            Event::class,
            ['listener' => 'some.Listener', 'priority' => 5, 'before' => 'A'],
        );
    }
}
