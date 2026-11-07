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

namespace Webware\Event\Container;

use Webware\Event\Exception\InvalidListenerConfigurationException;
use Webware\Event\ListenerProvider\PositionableListenerProvider;

use function is_array;
use function is_string;

/**
 * The ordering declaration of one configured listener spec.
 *
 * A spec may be a bare service id, or an array carrying `listener` plus `priority`, or `before`, or `after`,
 * or none of them. Priority and relative placement are separate ordering mechanisms that share no common
 * scale, so combining them is rejected rather than silently picking one. `before` and `after` are mutually
 * exclusive for the same reason.
 *
 * @internal
 */
final readonly class ListenerDeclaration
{
    private function __construct(
        public mixed $service,
        public ?string $listenerType,
        private ?int $priority,
        private ?string $before,
        private ?string $after,
    ) {}

    /**
     * @param array{listener?: mixed, priority?: int, before?: string, after?: string} $spec
     *
     * @throws InvalidListenerConfigurationException
     */
    public static function fromArray(string $eventType, array $spec): self
    {
        /** @var mixed $service */
        $service  = $spec['listener'] ?? null;
        $priority = $spec['priority'] ?? null;
        $before   = $spec['before'] ?? null;
        $after    = $spec['after'] ?? null;

        if (null !== $priority && (null !== $before || null !== $after)) {
            throw InvalidListenerConfigurationException::forConflictingOrdering($eventType);
        }

        if (null !== $before && null !== $after) {
            throw InvalidListenerConfigurationException::forConflictingRelation($eventType);
        }

        return new self(
            $service,
            self::listenerType($service),
            $priority,
            $before,
            $after,
        );
    }

    /**
     * Accepts a bare entry or a shaped one, so a plain service id and a positioned spec share one path.
     *
     * @throws InvalidListenerConfigurationException
     */
    public static function fromSpec(string $eventType, mixed $spec): self
    {
        /** @var array{listener?: mixed, priority?: int, before?: string, after?: string} $shape */
        $shape = is_array($spec) ? $spec : ['listener' => $spec];

        return self::fromArray($eventType, $shape);
    }

    /**
     * Derives the listener type from the configured service, which is what a config author can reference.
     */
    private static function listenerType(mixed $service): ?string
    {
        return is_string($service) ? $service : null;
    }

    public function registerWith(
        PositionableListenerProvider $provider,
        string $eventType,
        callable $listener,
    ): void {
        if (null !== $this->priority) {
            $provider->listen($eventType, $listener, $this->priority, $this->listenerType);

            return;
        }

        if (null !== $this->before) {
            $provider->listenBefore($this->before, $eventType, $listener, $this->listenerType);

            return;
        }

        if (null !== $this->after) {
            $provider->listenAfter($this->after, $eventType, $listener, $this->listenerType);

            return;
        }

        $provider->listen($eventType, $listener, null, $this->listenerType);
    }
}
