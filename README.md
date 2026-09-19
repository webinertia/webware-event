# webware/webware-event

[![PHP Version](https://img.shields.io/packagist/php-v/webware/webware-event)](https://packagist.org/packages/webware/webware-event)
[![Latest Version](https://img.shields.io/packagist/v/webware/webware-event)](https://packagist.org/packages/webware/webware-event)
[![License](https://img.shields.io/github/license/webinertia/webware-event)](LICENSE)
[![Continuous Integration](https://github.com/webinertia/webware-event/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/webinertia/webware-event/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/webinertia/webware-event/graph/badge.svg)](https://codecov.io/gh/webinertia/webware-event)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fwebinertia%2Fwebware-event%2F1.0.x)](https://dashboard.stryker-mutator.io/reports/github.com/webinertia/webware-event/1.0.x)

PSR-14 event system for the Mezzio framework — declarative listener wiring, delegator-based dispatcher injection, and PSR-15 middleware integration.

## Installation

```bash
composer require webware/webware-event
```

## Quick Start

### 1. Register the config provider

Merge `Webware\Event\ConfigProvider` into your application config (standard Laminas/Mezzio pattern).

### 2. Register listeners in app config

```php
return [
    'listeners' => [
        OrderPlaced::class => [
            ['listener' => UpdateInventory::class, 'priority' => 100],
        ],
    ],
    'listener_providers' => [
        CustomListenerProvider::class,
    ],
];
```

### 3. Dispatch events from your services

```php
use Webware\Event\EventDispatcherAwareInterface;
use Webware\Event\EventDispatcherAwareTrait;

class OrderService implements EventDispatcherAwareInterface
{
    use EventDispatcherAwareTrait;

    public function placeOrder(Order $order): void
    {
        // ...business logic...

        $this->eventDispatcher->dispatch(new Event('order.placed', $this, [
            'order_id' => $order->id,
        ]));
    }
}
```

To inject the dispatcher, wire the `EventDispatcherAwareDelegator` for each service that implements `EventDispatcherAwareInterface`:

```php
return [
    'dependencies' => [
        'delegators' => [
            OrderService::class => [
                EventDispatcherAwareDelegator::class,
            ],
        ],
    ],
];
```

### 4. Access the dispatcher in HTTP handlers

```php
class OrderHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $dispatcher = $request->getAttribute(EventDispatcherInterface::class);
        $dispatcher->dispatch(new Event('handler.invoked', $this));

        // ...
    }
}
```

The `EventDispatcherMiddleware` attaches the dispatcher as a request attribute. You must register it in your middleware pipeline to make the dispatcher available to all downstream Middleware/handlers.

## Configuration

### `ConfigProvider` keys

| Key | Type | Description |
| --- | --- | --- |
| `dependencies` | `array` | Container aliases & factories for the dispatcher, aggregate, and middleware. |
| `listeners` | `array<class-string, array>` | Event class → listener specs. Merged with app config. |
| `listener_providers` | `array<class-string>` | Additional `ListenerProviderInterface` FQCNs to attach to the aggregate. |

### Listener spec formats

| Format | Example | Behavior |
| --- | --- | --- |
| Class-string | `SendEmail::class` | Lazy-resolved from container via `LazyListener` |
| Array with `priority` | `['listener' => X::class, 'priority' => 100]` | Resolved via `PrioritizedListenerProvider` |
| Callable | `fn(Event $e) => ...` | Attached directly |

Allows an object to carry an event instance. Intended for listeners that need access to the event they're processing.

## Middleware

`EventDispatcherMiddleware` (PSR-15) injects the event dispatcher into the request as an attribute keyed by `EventDispatcherInterface::class`. Register it in your middleware pipeline to make the dispatcher available to all downstream handlers.

## Architecture

```text
ConfigProvider ──▶ container wiring (aliases, factories, listeners)

                         ┌──────────────────┐
                         │   Container       │
                         └──────┬───────────┘
                                │
              ┌─────────────────┼──────────────────┐
              ▼                 ▼                   ▼
   ListenerProviderAggregate   EventDispatcher   EventDispatcherMiddleware
   (resolves listeners)        (phly)            (PSR-15, injects into request)
              │                 │
              └────────┬────────┘
                       │
                 dispatch(Event)
                       │
              ┌────────┴────────┐
              ▼                 ▼
      prioritized listeners   standard listeners
```

### Key classes

| Class | Namespace | Role |
| --- | --- | --- |
| `Event` | `Webware\Event` | Concrete event with name, target, params, and propagation control |
| `ConfigProvider` | `Webware\Event` | Dependency wiring, default config, and the `ConfigShape` type alias |
| `ListenerProviderAggregateFactory` | `Webware\Event\Container` | Builds the listener aggregate from config |
| `EventDispatcherAwareDelegator` | `Webware\Event\Container` | Injects the dispatcher into aware services |
| `EventDispatcherMiddleware` | `Webware\Event\Http\Middleware` | PSR-15 middleware for request-scoped dispatch |
| `EventAwareInterface` / `EventAwareTrait` | `Webware\Event` | Pattern for event-carrying objects |
| `EventDispatcherAwareInterface` / `EventDispatcherAwareTrait` | `Webware\Event` | Pattern for event-dispatching services |
| `EventInterface` | `Webware\Event` | Contract an event satisfies; `Event` implements it |
| `ListenerInterface` | `Webware\Event` | Contract a listener satisfies (`__invoke(EventInterface $event): void`) |
| `EventPropagationInterface` / `EventPropagationTrait` | `Webware\Event` | Pattern for stoppable propagation |

Config arrays are typed by the `@type ConfigShape` alias declared on `Webware\Event\ConfigProvider` —
consumers `@import-type ConfigShape from ConfigProvider` and read the keys directly, rather than going
through an accessor class. The traits declare `@require-implements`, so mago reports a class that uses a
trait without also implementing its interface.

## Development

```bash
composer test              # unit suite
composer test-integration  # integration suite (in-process ServiceManager wiring)
composer test-coverage     # unit suite with clover + HTML coverage
composer mutation-test     # Infection, with Mago as staticAnalysisTool
composer test-all          # test + test-integration + mutation-test
```

Every command also runs in the tooling container, which needs no native PHP toolchain:

```bash
docker compose up -d
docker compose exec tooling composer test
docker compose exec tooling mago format --check
```

## License

BSD-3-Clause
