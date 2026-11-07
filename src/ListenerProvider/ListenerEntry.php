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

/**
 * A registered listener together with the ordering data needed to place it.
 *
 * `listenerType` is the string the config author wrote (a service id or class-string), which is what another
 * entry references in `before` or `after`. A listener registered without a type can be positioned against an
 * anchor but cannot itself be an anchor.
 *
 * @internal
 */
final readonly class ListenerEntry
{
    /**
     * @param null|string $listenerType the configured listener type, when the entry was declared with one
     * @param null|string $anchorType   the listener type this entry is positioned against, or null when unpositioned
     * @param bool        $placeAfter   whether the entry runs after its anchor, rather than before it
     */
    public function __construct(
        public Closure $listener,
        public ?string $listenerType = null,
        public ?int $priority = null,
        public ?string $anchorType = null,
        public bool $placeAfter = true,
    ) {}

    /**
     * Orders two roots for emission: highest priority first, unprioritized last.
     *
     * Equal priorities compare equal, and `usort()` is stable, so they keep registration order. An absent
     * priority is not a priority of 1: the unprioritized group runs after the whole numeric range.
     */
    public static function compareByPriority(self $left, self $right): int
    {
        if (null === $left->priority) {
            return null === $right->priority ? 0 : 1;
        }

        if (null === $right->priority) {
            return -1;
        }

        return $right->priority <=> $left->priority;
    }
}
