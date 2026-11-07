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

use function spl_object_id;

/**
 * Emits entries in their resolved order, placing positioned entries beside their anchors.
 *
 * A `before` child and its own children are emitted before the anchor, and an `after` child and its own
 * children after it, so a chain of any depth keeps its declared order.
 *
 * @internal
 */
final readonly class OrderingFlattener
{
    public function __construct(
        private OrderingGraph $graph,
    ) {}

    /**
     * @return list<ListenerEntry>
     */
    public function flatten(ListenerEntry $entry): array
    {
        $ordered  = [];
        $identity = spl_object_id($entry);

        foreach ($this->graph->before[$identity] ?? [] as $child) {
            foreach ($this->flatten($child) as $positioned) {
                $ordered[] = $positioned;
            }
        }

        $ordered[] = $entry;

        foreach ($this->graph->after[$identity] ?? [] as $child) {
            foreach ($this->flatten($child) as $positioned) {
                $ordered[] = $positioned;
            }
        }

        return $ordered;
    }

    /**
     * @return list<ListenerEntry>
     */
    public function flattenRoots(): array
    {
        $ordered = [];

        foreach ($this->graph->roots as $root) {
            foreach ($this->flatten($root) as $positioned) {
                $ordered[] = $positioned;
            }
        }

        return $ordered;
    }
}
