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

final readonly class ConfigProvider
{
    public const LISTENER_KEY = 'listeners';

    public const LISTENER_PROVIDER_KEY = 'listener_providers';

    /**
     * @return array{
     *     dependencies: array{aliases: array<class-string, class-string>, factories: array<class-string, class-string>},
     *     listeners: array<class-string, array<int, array{listener: callable|class-string, priority?: int}|class-string>>,
     *     listener_providers: class-string[],
     * }
     */
    public function __invoke(): array
    {
        return [
            'dependencies'              => $this->getDependencies(),
            self::LISTENER_KEY          => [],
            self::LISTENER_PROVIDER_KEY => [],
        ];
    }

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
                ListenerProviderAggregate::class            => Container\ListenerProviderAggregateFactory::class,
                Middleware\EventDispatcherMiddleware::class => Middleware\EventDispatcherMiddlewareFactory::class,
            ],
        ];
    }
}
