<?php

declare(strict_types=1);

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
     *     listeners: array<class-string, array<int, class-string|array{listener: callable|class-string, priority?: int}>>,
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
