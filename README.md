# webware/webware-event

[![PHP Version](https://img.shields.io/packagist/php-v/webware/webware-event)](https://packagist.org/packages/webware/webware-event)
[![Latest Version](https://img.shields.io/packagist/v/webware/webware-event)](https://packagist.org/packages/webware/webware-event)
[![License](https://img.shields.io/github/license/webinertia/webware-event)](LICENSE)
[![Continuous Integration](https://github.com/webinertia/webware-event/actions/workflows/required/webinertia/.github/.github/workflows/continuous-integration.yml/badge.svg)](https://github.com/webinertia/webware-event/actions/workflows/required/webinertia/.github/.github/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/webinertia/webware-event/graph/badge.svg)](https://codecov.io/gh/webinertia/webware-event)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fwebinertia%2Fwebware-event%2F1.0.x)](https://dashboard.stryker-mutator.io/reports/github.com/webinertia/webware-event/1.0.x)

PSR-14 event system for the Mezzio framework - declarative listener wiring, delegator-based dispatcher injection, and PSR-15 middleware integration.

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
| `listeners` | `array<class-string, array>` | Event class → listener specs, each optionally ordered by `priority`, `before`, or `after`. Merged with app config. |
| `listener_providers` | `array<class-string>` | Additional `ListenerProviderInterface` FQCNs to attach to the aggregate. |

Provider resolution fails loudly, on the same policy as listener resolution: a `listener_providers` entry the
container does not have, or one that resolves to something other than a `ListenerProviderInterface`, throws
`InvalidListenerConfigurationException` while the aggregate is built.

### Listener spec formats

| Format | Example | Behavior |
| --- | --- | --- |
| Class-string | `SendEmail::class` | Lazy-resolved from container via `LazyListener` |
| Array with `priority` | `['listener' => X::class, 'priority' => 100]` | Placed in the numeric range, highest number first |
| Array with `after` | `['listener' => X::class, 'after' => Y::class]` | Placed immediately after the listener named `Y` |
| Array with `before` | `['listener' => X::class, 'before' => Y::class]` | Placed immediately before the listener named `Y` |
| Callable | `fn(Event $e) => ...` | Attached directly |

Every string entry is a container service id resolved lazily through the container; a string is never treated as a callable. An id the container does not have - as well as an array spec with no `listener` key - throws `InvalidListenerConfigurationException` (a `ContainerExceptionInterface`) while the listener provider is built, rather than being skipped silently.

### Listener ordering

Every listener for an event is resolved by one provider, `PositionableListenerProvider`, so numeric priorities
and relative placement compose into a single sequence. Order is resolved when listeners are read for an event,
not when they are registered.

Resolution rules:

1. Entries carrying `priority` sort by number, descending. Equal numbers keep registration order.
2. An entry with no `priority` sorts after every prioritized entry. An absent priority is **not** a default
   priority of `1`: a listener declared at priority `-100` still runs before an unprioritized listener.
3. An entry with `before` or `after` runs immediately before or after the listener it names. Positioned
   listeners keep registration order among themselves, and positioning nests.

Resolving both kinds of ordering inside one provider is deliberate. `ListenerProviderAggregate` yields provider
by provider, so relative placement in a tier of its own would form one contiguous block that could never
interleave with prioritized listeners. One provider is what lets an `after` listener sit between two prioritized
listeners.

### Relative placement

```php
'listeners' => [
    SomeEvent::class => [
        FirstListener::class,
        ['listener' => SecondListener::class, 'after' => FirstListener::class],
        ['listener' => ThirdListener::class, 'before' => FirstListener::class],
    ],
],
```

That config runs `ThirdListener`, then `FirstListener`, then `SecondListener`. Relative placement lets a package
say "after that listener" without knowing its priority, so two packages contributing listeners for the same event
do not have to agree on numbers in one global priority space.

A listener is identified by the string written in config, which is the container service id or class-string, so
`after` names the same string the anchor is registered under. A plain callable has no identifier: it can be
positioned relative to an anchor, but nothing can be positioned relative to it.

- Forward references work. `after` may name a listener declared later in the same event's list, because ordering
  resolves when listeners are read.
- An unknown anchor throws `InvalidListenerConfigurationException` while the aggregate is built.
- A cycle throws `InvalidListenerConfigurationException` naming the path, for example `A -> B -> A`. A listener
  positioned relative to itself is the one-element cycle `A -> A`.
- `priority` cannot be combined with `before` or `after`, and `before` cannot be combined with `after`. Each
  throws `InvalidListenerConfigurationException`.
- When one listener type is registered more than once, the first registration is the anchor.

### Registering the same listener twice

Registration is a declaration of interest, so declaring interest twice makes the listener run twice. There is no
suppression: two entries naming the same listener class both fire, in the order the entries appear in config.

This is deliberate and pinned by a test. The vendor providers dedupe with `in_array($listener, $listeners, true)`,
which matches only an identical callable instance; the factory wraps each string entry in its own `LazyListener`,
so two entries naming the same class are distinct callables and the dedupe never sees them as equal. Applications
that want a listener to run once should register it once.

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
| `EventAwareInterface` / `EventAwareTrait` | `Webware\Event` | Pattern for event-carrying objects: an object can carry the event it is processing |
| `EventDispatcherAwareInterface` / `EventDispatcherAwareTrait` | `Webware\Event` | Pattern for event-dispatching services |
| `EventInterface` | `Webware\Event` | Contract an event satisfies; `Event` implements it |
| `ListenerInterface` | `Webware\Event` | Contract a listener satisfies (`__invoke(EventInterface $event): void`) |
| `EventPropagationInterface` / `EventPropagationTrait` | `Webware\Event` | Pattern for stoppable propagation |

Config arrays are typed by the `@type ConfigShape` alias declared on `Webware\Event\ConfigProvider` -
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
