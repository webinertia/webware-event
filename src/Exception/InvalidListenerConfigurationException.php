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

namespace Webware\Event\Exception;

use Psr\Container\ContainerExceptionInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use RuntimeException;

use function get_debug_type;
use function implode;
use function sprintf;

/**
 * A configured listener entry cannot be turned into a callable.
 *
 * Implements the PSR-11 container exception contract, so callers catching the
 * documented `ContainerExceptionInterface` from the aggregate factory cover it.
 *
 * @api
 */
final class InvalidListenerConfigurationException extends RuntimeException implements ContainerExceptionInterface
{
    public static function forConflictingOrdering(string $eventType): self
    {
        return new self(sprintf(
            'A listener for event "%s" declares "priority" together with "before" or "after";'
                . ' priority and relative placement cannot be combined.',
            $eventType,
        ));
    }

    public static function forConflictingRelation(string $eventType): self
    {
        return new self(sprintf(
            'A listener for event "%s" declares both "before" and "after"; use one or the other.',
            $eventType,
        ));
    }

    /**
     * @param list<string> $listenerTypes the cycle, in order, with the first type repeated to close it
     */
    public static function forCyclicOrdering(string $eventType, array $listenerTypes): self
    {
        return new self(sprintf(
            'Listener ordering for event "%s" contains a cycle: %s.',
            $eventType,
            implode(' -> ', $listenerTypes),
        ));
    }

    public static function forInvalidEntry(mixed $entry): self
    {
        return new self(sprintf(
            'A listener entry must be a container service id or a callable; received %s.',
            get_debug_type($entry),
        ));
    }

    public static function forInvalidProviderService(string $service, mixed $provider): self
    {
        return new self(sprintf(
            'Listener provider "%s" must implement %s; received %s.',
            $service,
            ListenerProviderInterface::class,
            get_debug_type($provider),
        ));
    }

    public static function forUnresolvableListenerReference(string $eventType, string $reference): self
    {
        return new self(sprintf(
            'Listener reference "%s" for event "%s" does not name a listener registered for that event.',
            $reference,
            $eventType,
        ));
    }

    public static function forUnresolvableProviderService(string $service): self
    {
        return new self(sprintf(
            'Listener provider "%s" is not registered in the container.',
            $service,
        ));
    }

    public static function forUnresolvableService(string $service): self
    {
        return new self(sprintf(
            'Listener service "%s" is not registered in the container.',
            $service,
        ));
    }
}
