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

use function array_key_exists;
use function array_keys;
use function array_slice;
use function count;
use function spl_object_id;

/**
 * Rejects anchor chains that close on themselves.
 *
 * Every positioned entry has exactly one anchor, so the chains form a forest unless one loops. A self
 * reference is the shortest such loop and reports as `A -> A`. A loop cannot be emitted in any order that
 * honours every `before` and `after`, so it is a configuration error rather than something to break
 * arbitrarily.
 *
 * @internal
 */
final readonly class AnchorCycleGuard
{
    /**
     * @param array<int, array{anchor: string, parent: ListenerEntry, placeAfter: bool}> $edges
     *
     * @throws InvalidListenerConfigurationException
     */
    public static function assertAcyclic(string $eventType, array $edges): void
    {
        foreach (array_keys($edges) as $start) {
            $offsets = [];
            $path    = [];
            $node    = $start;

            while (array_key_exists($node, $edges)) {
                if (array_key_exists($node, $offsets)) {
                    throw InvalidListenerConfigurationException::forCyclicOrdering(
                        $eventType,
                        self::describeCycle($path, $offsets[$node], $edges[$node]['anchor']),
                    );
                }

                $offsets[$node] = count($path);
                $path[]         = $edges[$node]['anchor'];
                $node           = spl_object_id($edges[$node]['parent']);
            }
        }
    }

    /**
     * @param list<string> $path    the anchor type of each walked entry, in walk order
     * @param int          $offset  the position the loop starts at
     * @param string       $closing the anchor type that closes the loop
     *
     * @return list<string>
     */
    private static function describeCycle(array $path, int $offset, string $closing): array
    {
        $cycle   = array_slice($path, $offset);
        $cycle[] = $closing;

        return $cycle;
    }
}
