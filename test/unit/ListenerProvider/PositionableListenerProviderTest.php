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

namespace WebwareTest\Event\ListenerProvider;

use ArrayObject;
use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Event\Event;
use Webware\Event\Exception\InvalidListenerConfigurationException;
use Webware\Event\ListenerProvider\AnchorCycleGuard;
use Webware\Event\ListenerProvider\ListenerEntry;
use Webware\Event\ListenerProvider\OrderingFlattener;
use Webware\Event\ListenerProvider\OrderingGraph;
use Webware\Event\ListenerProvider\PositionableListenerProvider;

/**
 * Every ordering test asserts the whole emitted sequence rather than the
 * presence of the expected listeners, so a transposed `before` and `after`, an
 * inverted priority comparison or an off-by-one in the insertion order all fail
 * instead of passing on a subset.
 */
#[CoversClass(AnchorCycleGuard::class)]
#[CoversClass(ListenerEntry::class)]
#[CoversClass(OrderingFlattener::class)]
#[CoversClass(OrderingGraph::class)]
#[CoversClass(PositionableListenerProvider::class)]
#[CoversMethod(AnchorCycleGuard::class, 'assertAcyclic')]
#[CoversMethod(ListenerEntry::class, 'compareByPriority')]
#[CoversMethod(OrderingFlattener::class, 'flatten')]
#[CoversMethod(OrderingFlattener::class, 'flattenRoots')]
#[CoversMethod(OrderingGraph::class, 'fromEntries')]
#[CoversMethod(PositionableListenerProvider::class, 'getListenersForEvent')]
#[CoversMethod(PositionableListenerProvider::class, 'listen')]
#[CoversMethod(PositionableListenerProvider::class, 'listenAfter')]
#[CoversMethod(PositionableListenerProvider::class, 'listenBefore')]
#[CoversMethod(PositionableListenerProvider::class, 'validateOrdering')]
final class PositionableListenerProviderTest extends TestCase
{
    /** @var list<string> */
    private array $log = [];

    #[Test]
    public function addingAListenerInvalidatesTheResolvedOrdering(): void
    {
        $provider = new PositionableListenerProvider();

        $provider->listen(Event::class, $this->listener('anchor'), null, 'Anchor');

        static::assertSame(['anchor'], $this->emit($provider));

        $provider->listenBefore('Anchor', Event::class, $this->listener('before'));

        static::assertSame(['before', 'anchor'], $this->emit($provider));

        $provider->listen(Event::class, $this->listener('tail'));

        static::assertSame(['before', 'anchor', 'tail'], $this->emit($provider));
    }

    #[Test]
    public function aDuplicateListenerInstanceFiresOnceForEachRegistration(): void
    {
        $provider = new PositionableListenerProvider();
        $listener = $this->listener('once');

        $provider->listen(Event::class, $listener);
        $provider->listen(Event::class, $listener);

        static::assertSame(['once', 'once'], $this->emit($provider));
    }

    #[Test]
    public function aListenerIsOnlyEmittedForEventsItWasRegisteredAgainst(): void
    {
        $provider = new PositionableListenerProvider();

        $provider->listen(Event::class, $this->listener('event'));
        $provider->listen(ArrayObject::class, $this->listener('array object'));

        static::assertSame(['event'], $this->emit($provider, new Event()));
        static::assertSame(['array object'], $this->emit($provider, new ArrayObject()));
    }

    #[Test]
    public function anAnchorMayCarrySeveralChildrenAndKeepsTheirRegistrationOrder(): void
    {
        $provider = new PositionableListenerProvider();

        $provider->listen(Event::class, $this->listener('anchor'), null, 'Anchor');
        $provider->listenAfter('Anchor', Event::class, $this->listener('after one'));
        $provider->listenAfter('Anchor', Event::class, $this->listener('after two'));
        $provider->listenBefore('Anchor', Event::class, $this->listener('before one'));
        $provider->listenBefore('Anchor', Event::class, $this->listener('before two'));

        static::assertSame(
            ['before one', 'before two', 'anchor', 'after one', 'after two'],
            $this->emit($provider),
        );
    }

    #[Test]
    public function anEntryConstructedWithoutAPlacementDefaultsToAfter(): void
    {
        $entry = new ListenerEntry(static function (): void {});

        static::assertTrue($entry->placeAfter);
    }

    #[Test]
    public function anUnprioritizedListenerRunsAfterTheWholeNumericRange(): void
    {
        $provider = new PositionableListenerProvider();

        $provider->listen(Event::class, $this->listener('unprioritized one'));
        $provider->listen(Event::class, $this->listener('negative'), -100);
        $provider->listen(Event::class, $this->listener('unprioritized two'));
        $provider->listen(Event::class, $this->listener('positive'), 10);

        static::assertSame(
            ['positive', 'negative', 'unprioritized one', 'unprioritized two'],
            $this->emit($provider),
        );
    }

    #[Test]
    public function anUnresolvableReferenceIsRejectedWhenTheOrderingIsResolved(): void
    {
        $provider = new PositionableListenerProvider();

        $provider->listenAfter('Missing', Event::class, $this->listener('child'));

        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage(
            'Listener reference "Missing" for event "Webware\Event\Event"'
                . ' does not name a listener registered for that event.',
        );

        $this->emit($provider);
    }

    #[Test]
    public function anUnresolvableReferenceIsRejectedWhenTheOrderingIsValidated(): void
    {
        $provider = new PositionableListenerProvider();

        $provider->listenBefore('Missing', Event::class, $this->listener('child'));

        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage(
            'Listener reference "Missing" for event "Webware\Event\Event"'
                . ' does not name a listener registered for that event.',
        );

        $provider->validateOrdering();
    }

    #[Test]
    public function anUntypedListenerCanBePositionedButCannotAnchor(): void
    {
        $provider = new PositionableListenerProvider();

        $provider->listen(Event::class, $this->listener('anchor'), null, 'Anchor');
        $provider->listenAfter('Anchor', Event::class, $this->listener('untyped child'));
        $provider->listen(Event::class, $this->listener('tail'));

        static::assertSame(['anchor', 'untyped child', 'tail'], $this->emit($provider));
    }

    #[Test]
    public function aProviderWithNoListenersEmitsNothing(): void
    {
        static::assertSame([], $this->emit(new PositionableListenerProvider()));
    }

    #[Test]
    public function aRhoShapedCycleReportsOnlyTheLoop(): void
    {
        $provider = new PositionableListenerProvider();

        $provider->listenAfter('B', Event::class, $this->listener('C'), 'C');
        $provider->listenAfter('A', Event::class, $this->listener('B'), 'B');
        $provider->listenAfter('B', Event::class, $this->listener('A'), 'A');

        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage(
            'Listener ordering for event "Webware\Event\Event" contains a cycle: A -> B -> A.',
        );

        $this->emit($provider);
    }

    #[Test]
    public function aSelfReferenceIsReportedAsACycle(): void
    {
        $provider = new PositionableListenerProvider();

        $provider->listenAfter('Self', Event::class, $this->listener('self'), 'Self');

        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage(
            'Listener ordering for event "Webware\Event\Event" contains a cycle: Self -> Self.',
        );

        $provider->validateOrdering();
    }

    #[Test]
    public function aTwoListenerCycleIsReportedWithItsPath(): void
    {
        $provider = new PositionableListenerProvider();

        $provider->listenAfter('B', Event::class, $this->listener('A'), 'A');
        $provider->listenAfter('A', Event::class, $this->listener('B'), 'B');

        $this->expectException(InvalidListenerConfigurationException::class);
        $this->expectExceptionMessage(
            'Listener ordering for event "Webware\Event\Event" contains a cycle: B -> A -> B.',
        );

        $this->emit($provider);
    }

    #[Test]
    public function beforeAndAfterPlaceTheListenerOnOppositeSidesOfItsAnchor(): void
    {
        $provider = new PositionableListenerProvider();

        $provider->listen(Event::class, $this->listener('anchor'), null, 'Anchor');
        $provider->listenAfter('Anchor', Event::class, $this->listener('after'));
        $provider->listenBefore('Anchor', Event::class, $this->listener('before'));

        static::assertSame(['before', 'anchor', 'after'], $this->emit($provider));
    }

    #[Test]
    public function compareByPriorityRanksDescendingWithAnAbsentPriorityLast(): void
    {
        static::assertSame(0, ListenerEntry::compareByPriority($this->entry(null), $this->entry(null)));
        static::assertSame(0, ListenerEntry::compareByPriority($this->entry(5), $this->entry(5)));
        static::assertSame(1, ListenerEntry::compareByPriority($this->entry(null), $this->entry(5)));
        static::assertSame(-1, ListenerEntry::compareByPriority($this->entry(5), $this->entry(null)));
        static::assertSame(-1, ListenerEntry::compareByPriority($this->entry(0), $this->entry(null)));
        static::assertSame(-1, ListenerEntry::compareByPriority($this->entry(-100), $this->entry(null)));
        static::assertSame(1, ListenerEntry::compareByPriority($this->entry(null), $this->entry(-100)));
        static::assertSame(1, ListenerEntry::compareByPriority($this->entry(5), $this->entry(10)));
        static::assertSame(-1, ListenerEntry::compareByPriority($this->entry(10), $this->entry(5)));
    }

    #[Test]
    public function equalPrioritiesKeepRegistrationOrder(): void
    {
        $provider = new PositionableListenerProvider();

        $provider->listen(Event::class, $this->listener('first'), 5);
        $provider->listen(Event::class, $this->listener('second'), 5);

        static::assertSame(['first', 'second'], $this->emit($provider));
    }

    #[Test]
    public function flattenEmitsASingleEntryWhenItHasNoChildren(): void
    {
        $entry     = $this->entry(5);
        $flattener = new OrderingFlattener(OrderingGraph::fromEntries(Event::class, [$entry]));

        static::assertSame([$entry], $flattener->flatten($entry));
    }

    #[Test]
    public function positionedChildrenKeepTheirOwnPositionedChildren(): void
    {
        $provider = new PositionableListenerProvider();

        $provider->listen(Event::class, $this->listener('anchor'), null, 'Anchor');
        $provider->listenAfter('Anchor', Event::class, $this->listener('after'), 'After');
        $provider->listenAfter('After', Event::class, $this->listener('after after'));
        $provider->listenBefore('Anchor', Event::class, $this->listener('before'), 'Before');
        $provider->listenBefore('Before', Event::class, $this->listener('before before'));

        static::assertSame(
            ['before before', 'before', 'anchor', 'after', 'after after'],
            $this->emit($provider),
        );
    }

    #[Test]
    public function prioritizedListenersRunHighestPriorityFirst(): void
    {
        $provider = new PositionableListenerProvider();

        $provider->listen(Event::class, $this->listener('low'), 5);
        $provider->listen(Event::class, $this->listener('high'), 50);
        $provider->listen(Event::class, $this->listener('mid'), 10);

        static::assertSame(['high', 'mid', 'low'], $this->emit($provider));
    }

    #[Test]
    public function theFirstRegistrationOfATypeIsWhatAnAnchorResolvesTo(): void
    {
        $provider = new PositionableListenerProvider();

        $provider->listen(Event::class, $this->listener('first declaration'), null, 'A');
        $provider->listen(Event::class, $this->listener('second declaration'), null, 'A');
        $provider->listenAfter('A', Event::class, $this->listener('child'));

        static::assertSame(['first declaration', 'child', 'second declaration'], $this->emit($provider));
    }

    #[Test]
    public function validateOrderingAcceptsAnAcyclicConfiguration(): void
    {
        $provider = new PositionableListenerProvider();

        $provider->listen(Event::class, $this->listener('anchor'), null, 'Anchor');
        $provider->listenAfter('Anchor', Event::class, $this->listener('after'), 'After');
        $provider->listenBefore('After', Event::class, $this->listener('between'), 'Between');

        $provider->validateOrdering();

        static::assertSame(['anchor', 'between', 'after'], $this->emit($provider));
    }

    /**
     * @return list<string>
     */
    private function emit(PositionableListenerProvider $provider, ?object $event = null): array
    {
        $this->log = [];
        $event     ??= new Event();

        foreach ($provider->getListenersForEvent($event) as $listener) {
            $listener($event);
        }

        return $this->log;
    }

    private function entry(?int $priority): ListenerEntry
    {
        return new ListenerEntry(static function (): void {}, null, $priority);
    }

    private function listener(string $label): Closure
    {
        return function (object $event) use ($label): void {
            $this->log[] = $label;
        };
    }
}
