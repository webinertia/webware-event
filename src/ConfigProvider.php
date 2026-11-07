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

namespace Webware\Event;

use Phly\EventDispatcher\EventDispatcher;
use Phly\EventDispatcher\ListenerProvider\ListenerProviderAggregate;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Listener ordering.
 *
 * A listener spec carrying a `priority` runs in descending priority order. A spec without a priority runs in
 * declaration order, after every listener that has a priority. An absent priority is therefore not a priority
 * of 1: a listener registered at priority -100 still fires before an unprioritized listener, because the
 * unprioritized group runs after the entire numeric range.
 *
 * A spec may instead position itself relative to another listener for the same event with `after` or
 * `before`, naming the service id or class-string the other spec was declared with. Relative placement needs no
 * shared numeric space, so separate packages can order themselves against a listener they do not own. The
 * reference may point at a spec declared later, and an unresolvable reference or a cycle is a configuration
 * error. A spec may not combine `priority` with `before` or `after`, nor declare both `before` and `after`.
 *
 * Priority and relative placement both resolve inside `PositionableListenerProvider`. They cannot be split
 * across provider tiers: `ListenerProviderAggregate` yields one provider at a time, so a relative tier would
 * form a contiguous block that could never sit between prioritized listeners.
 *
 * @type ConfigShape = array{
 *     listeners: array<class-string, array<int, array{listener: class-string<ListenerInterface>|callable, priority?: int, before?: string, after?: string}|class-string<ListenerInterface>|callable>>,
 *     listener_providers: list<class-string>,
 *     ...<string, mixed>,
 * }
 */
final readonly class ConfigProvider
{
    public const string LISTENER_KEY = 'listeners';

    public const string LISTENER_PROVIDER_KEY = 'listener_providers';

    /**
     * @return array{
     *     aliases: array<class-string, class-string>,
     *     factories: array<class-string, class-string>,
     * }
     */
    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                EventDispatcherInterface::class  => EventDispatcher::class,
                ListenerProviderInterface::class => ListenerProviderAggregate::class,
            ],
            'factories' => [
                ListenerProviderAggregate::class                 => Container\ListenerProviderAggregateFactory::class,
                Http\Middleware\EventDispatcherMiddleware::class => Http\Middleware\Container\EventDispatcherMiddlewareFactory::class,
            ],
        ];
    }

    /**
     * @return ConfigShape
     */
    public function __invoke(): array
    {
        return [
            'dependencies'              => $this->getDependencies(),
            self::LISTENER_KEY          => [],
            self::LISTENER_PROVIDER_KEY => [],
        ];
    }
}
