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

namespace WebwareTest\Event;

use ArrayObject;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use RuntimeException;
use Webware\Event\Event;
use Webware\Event\Exception\InvalidListenerConfigurationException;

#[CoversClass(InvalidListenerConfigurationException::class)]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forConflictingOrdering')]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forConflictingRelation')]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forCyclicOrdering')]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forInvalidEntry')]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forInvalidProviderService')]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forUnresolvableListenerReference')]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forUnresolvableProviderService')]
#[CoversMethod(InvalidListenerConfigurationException::class, 'forUnresolvableService')]
final class InvalidListenerConfigurationExceptionTest extends TestCase
{
    #[Test]
    public function forConflictingOrderingRejectsPriorityWithRelativePlacement(): void
    {
        $exception = InvalidListenerConfigurationException::forConflictingOrdering(Event::class);

        static::assertSame(
            'A listener for event "Webware\Event\Event" declares "priority" together with "before" or "after";'
                . ' priority and relative placement cannot be combined.',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function forConflictingRelationRejectsBothRelations(): void
    {
        $exception = InvalidListenerConfigurationException::forConflictingRelation(Event::class);

        static::assertSame(
            'A listener for event "Webware\Event\Event" declares both "before" and "after"; use one or the other.',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function forCyclicOrderingJoinsTheCycleWithArrows(): void
    {
        $exception = InvalidListenerConfigurationException::forCyclicOrdering(
            Event::class,
            ['A', 'B', 'A'],
        );

        static::assertSame(
            'Listener ordering for event "Webware\Event\Event" contains a cycle: A -> B -> A.',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function forInvalidEntryDescribesTheReceivedType(): void
    {
        $exception = InvalidListenerConfigurationException::forInvalidEntry(['priority' => 5]);

        static::assertSame(
            'A listener entry must be a container service id or a callable; received array.',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function forInvalidProviderServiceNamesTheProviderIdAndReceivedType(): void
    {
        $exception = InvalidListenerConfigurationException::forInvalidProviderService(
            'some.Service',
            new ArrayObject(),
        );

        static::assertSame(
            'Listener provider "some.Service" must implement Psr\EventDispatcher\ListenerProviderInterface;'
                . ' received ArrayObject.',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function forUnresolvableListenerReferenceNamesTheReferenceAndEvent(): void
    {
        $exception = InvalidListenerConfigurationException::forUnresolvableListenerReference(
            Event::class,
            'Missing',
        );

        static::assertSame(
            'Listener reference "Missing" for event "Webware\Event\Event"'
                . ' does not name a listener registered for that event.',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function forUnresolvableProviderServiceNamesTheProviderId(): void
    {
        $exception = InvalidListenerConfigurationException::forUnresolvableProviderService('not.registered.Provider');

        static::assertSame(
            'Listener provider "not.registered.Provider" is not registered in the container.',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function forUnresolvableServiceNamesTheServiceId(): void
    {
        $exception = InvalidListenerConfigurationException::forUnresolvableService('not.registered.Service');

        static::assertSame(
            'Listener service "not.registered.Service" is not registered in the container.',
            $exception->getMessage(),
        );
    }

    #[Test]
    public function itSatisfiesTheContainerExceptionContract(): void
    {
        $exception = InvalidListenerConfigurationException::forInvalidEntry(null);

        static::assertInstanceOf(RuntimeException::class, $exception);
        static::assertInstanceOf(ContainerExceptionInterface::class, $exception);
    }
}
