# webware/webware-event

[![PHP Version](https://img.shields.io/badge/php-~8.4%20%7C%7C%20~8.5-blue)](https://www.php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%2010-brightgreen)](phpstan.neon.dist)
[![License](https://img.shields.io/badge/license-BSD--3--Clause-green)](LICENSE)

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
| `ConfigProvider` | `Webware\Event` | Dependency wiring and default config |
| `Configuration` | `Webware\Event\Container` | Typed, validated config extraction from the container |
| `ListenerProviderAggregateFactory` | `Webware\Event\Container` | Builds the listener aggregate from config |
| `EventDispatcherAwareDelegator` | `Webware\Event\Container` | Injects the dispatcher into aware services |
| `EventDispatcherMiddleware` | `Webware\Event\Middleware` | PSR-15 middleware for request-scoped dispatch |
| `EventAwareInterface` / `EventAwareTrait` | `Webware\Event` | Pattern for event-carrying objects |
| `EventDispatcherAwareInterface` / `EventDispatcherAwareTrait` | `Webware\Event` | Pattern for event-dispatching services |

## Development

```bash
composer check-all    # Run coding standards, static analysis, and tests
composer cs-fix       # Auto-fix coding standard violations
composer sa           # PHPStan level 10 static analysis
composer test         # PHPUnit test suite
```

## License

BSD-3-Clause
