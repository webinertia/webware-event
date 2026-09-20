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
 * @type ConfigShape = array{
 *     listeners: array<class-string, array<int, array{listener: class-string<ListenerInterface>|callable, priority?: int}|class-string<ListenerInterface>|callable>>,
 *     listener_providers: list<class-string>,
 *     ...<string, mixed>,
 * }
 */
final readonly class ConfigProvider
{
    public const string LISTENER_KEY = 'listeners';

    public const string LISTENER_PROVIDER_KEY = 'listener_providers';

    /**
     * @return array{aliases: array<class-string, class-string>, factories: array<class-string, class-string>}
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
