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

namespace Webware\Event\ListenerProvider;

use Closure;
use Override;
use Phly\EventDispatcher\ListenerProvider\PositionableListenerProviderInterface;
use Webware\Event\Exception\InvalidListenerConfigurationException;

use function array_key_exists;

/**
 * Resolves numeric priority and relative placement in a single provider.
 *
 * `ListenerProviderAggregate` yields one provider at a time, so provider tiers cannot interleave: relative
 * placement kept in a tier of its own would form one contiguous block that could never sit between prioritized
 * listeners. Priority and relative placement therefore resolve together here, in one provider that replaces
 * both of phly's providers rather than adding a tier beside them.
 *
 * Ordering for an event type: prioritized listeners come first, highest priority first, then unprioritized
 * listeners, each group in registration order. A listener positioned with `before` or `after` is emitted
 * beside its anchor, so a package can order itself against another package's listener without knowing its
 * priority.
 *
 * A listener type is the string written in config, which is the service id or class-string. Anchors resolve
 * when listeners are read, so a reference may point at an entry registered later.
 *
 * @api
 */
final class PositionableListenerProvider implements PositionableListenerProviderInterface
{
    /** @var array<string, list<ListenerEntry>> event type => entries in registration order */
    private array $listeners = [];

    /** @var array<string, list<ListenerEntry>> event type => resolved ordering */
    private array $ordered = [];

    /**
     * @return list<callable>
     *
     * @throws InvalidListenerConfigurationException
     */
    // PSR-14 declares `@return iterable[callable]` in offset syntax, so the inherited return type is
    // unsatisfiable by any implementation (see vendor/psr/event-dispatcher/src/ListenerProviderInterface.php:14).
    // @mago-expect analysis:incompatible-return-type
    #[Override]
    public function getListenersForEvent(object $event): iterable
    {
        /** @var list<callable> $listeners */
        $listeners = [];

        foreach ($this->listeners as $eventType => $entries) {
            if (! $event instanceof $eventType) {
                continue;
            }

            foreach ($this->resolve($eventType, $entries) as $entry) {
                $listeners[] = $entry->listener;
            }
        }

        return $listeners;
    }

    #[Override]
    public function listen(
        string $eventType,
        callable $listener,
        ?int $priority = null,
        ?string $listenerType = null,
    ): void {
        $this->add($eventType, new ListenerEntry(Closure::fromCallable($listener), $listenerType, $priority));
    }

    #[Override]
    public function listenAfter(
        string $listenerTypeToAppend,
        string $eventType,
        callable $newListener,
        ?string $listenerType = null,
    ): void {
        $this->add(
            $eventType,
            new ListenerEntry(Closure::fromCallable($newListener), $listenerType, null, $listenerTypeToAppend, true),
        );
    }

    #[Override]
    public function listenBefore(
        string $listenerTypeToPrepend,
        string $eventType,
        callable $newListener,
        ?string $listenerType = null,
    ): void {
        $this->add(
            $eventType,
            new ListenerEntry(Closure::fromCallable($newListener), $listenerType, null, $listenerTypeToPrepend, false),
        );
    }

    /**
     * Resolves the ordering of every registered event type.
     *
     * Called once registration is complete, so an unresolvable reference or a cycle fails while the provider
     * is built rather than when the affected event is first dispatched.
     *
     * @throws InvalidListenerConfigurationException
     */
    public function validateOrdering(): void
    {
        foreach ($this->listeners as $eventType => $entries) {
            $this->resolve($eventType, $entries);
        }
    }

    private function add(string $eventType, ListenerEntry $entry): void
    {
        $this->listeners[$eventType][] = $entry;

        unset($this->ordered[$eventType]);
    }

    /**
     * @param list<ListenerEntry> $entries
     *
     * @return list<ListenerEntry>
     *
     * @throws InvalidListenerConfigurationException
     */
    private function order(string $eventType, array $entries): array
    {
        return new OrderingFlattener(OrderingGraph::fromEntries($eventType, $entries))->flattenRoots();
    }

    /**
     * @param list<ListenerEntry> $entries
     *
     * @return list<ListenerEntry>
     *
     * @throws InvalidListenerConfigurationException
     */
    private function resolve(string $eventType, array $entries): array
    {
        if (! array_key_exists($eventType, $this->ordered)) {
            $this->ordered[$eventType] = $this->order($eventType, $entries);
        }

        return $this->ordered[$eventType];
    }
}
