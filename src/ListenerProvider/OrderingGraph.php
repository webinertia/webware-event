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

use Webware\Event\Exception\InvalidListenerConfigurationException;

use function spl_object_id;
use function usort;

/**
 * The resolved anchor relationships for one event type.
 *
 * Anchors are resolved here, once, from the listener types declared for the event, which is why a reference
 * may point at an entry registered later: nothing is matched at registration time. A reference to a type that
 * is not declared, or a set of references that closes a loop, throws rather than silently dropping a listener.
 *
 * Entries are keyed by object identity rather than by position, so a child list never has to guess an index.
 *
 * @internal
 */
final class OrderingGraph
{
    /**
     * @param list<ListenerEntry>             $roots  entries that are not positioned, ordered for emission
     * @param array<int, list<ListenerEntry>> $before anchor identity => positioned entries that run before it
     * @param array<int, list<ListenerEntry>> $after  anchor identity => positioned entries that run after it
     */
    private function __construct(
        public array $roots,
        public array $before,
        public array $after,
    ) {}

    /**
     * @param list<ListenerEntry> $entries
     *
     * @throws InvalidListenerConfigurationException
     */
    public static function fromEntries(string $eventType, array $entries): self
    {
        $edges = self::edges($eventType, $entries, self::positions($entries));

        AnchorCycleGuard::assertAcyclic($eventType, $edges);

        $before = [];
        $after  = [];
        $roots  = [];

        foreach ($entries as $entry) {
            $edge = $edges[spl_object_id($entry)] ?? null;

            if (null === $edge) {
                $roots[] = $entry;

                continue;
            }

            if ($edge['placeAfter']) {
                $after[spl_object_id($edge['parent'])][] = $entry;

                continue;
            }

            $before[spl_object_id($edge['parent'])][] = $entry;
        }

        usort($roots, ListenerEntry::compareByPriority(...));

        return new self($roots, $before, $after);
    }

    /**
     * Maps each positioned entry to the entry it is anchored to.
     *
     * @param list<ListenerEntry>          $entries
     * @param array<string, ListenerEntry> $positions
     *
     * @return array<int, array{anchor: string, parent: ListenerEntry, placeAfter: bool}>
     *
     * @throws InvalidListenerConfigurationException
     */
    private static function edges(string $eventType, array $entries, array $positions): array
    {
        $edges = [];

        foreach ($entries as $entry) {
            $anchorType = $entry->anchorType;

            if (null === $anchorType) {
                continue;
            }

            $parent = $positions[$anchorType] ?? null;

            if (null === $parent) {
                throw InvalidListenerConfigurationException::forUnresolvableListenerReference(
                    $eventType,
                    $anchorType,
                );
            }

            $edges[spl_object_id($entry)] = [
                'anchor'     => $anchorType,
                'parent'     => $parent,
                'placeAfter' => $entry->placeAfter,
            ];
        }

        return $edges;
    }

    /**
     * Maps each declared listener type to its first declaration.
     *
     * Later declarations of the same type are emitted in their own right, but the first one is what an anchor
     * resolves to.
     *
     * @param list<ListenerEntry> $entries
     *
     * @return array<string, ListenerEntry>
     */
    private static function positions(array $entries): array
    {
        $positions = [];

        foreach ($entries as $entry) {
            $listenerType = $entry->listenerType;

            if (null === $listenerType) {
                continue;
            }

            $positions[$listenerType] ??= $entry;
        }

        return $positions;
    }
}
