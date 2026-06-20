<?php

declare(strict_types=1);

namespace Webware\EventTest;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\TestCase;
use Webware\Event\Event;
use Webware\Event\EventInterface;
use Webware\Event\EventPropagationInterface;
use Webware\Event\EventPropagationTrait;

#[CoversClass(Event::class)]
#[CoversClass(EventPropagationTrait::class)]
#[CoversMethod(Event::class, 'getName')]
#[CoversMethod(Event::class, 'setName')]
#[CoversMethod(Event::class, 'getTarget')]
#[CoversMethod(Event::class, 'setTarget')]
#[CoversMethod(Event::class, 'getParam')]
#[CoversMethod(Event::class, 'setParam')]
#[CoversMethod(Event::class, 'getParams')]
#[CoversMethod(Event::class, 'setParams')]
#[CoversMethod(Event::class, 'stopPropagation')]
#[CoversMethod(EventPropagationTrait::class, 'stopPropagation')]
#[CoversMethod(EventPropagationTrait::class, 'isPropagationStopped')]
final class EventTest extends TestCase
{
    public function testGetNameReturnsClassNameByDefault(): void
    {
        $event = new Event();
        self::assertSame(Event::class, $event->getName());
    }

    public function testGetNameReturnsProvidedName(): void
    {
        $event = new Event('test.event');
        self::assertSame('test.event', $event->getName());
    }

    public function testSetNameOverridesDefault(): void
    {
        $event = new Event();
        $event->setName('custom.event');
        self::assertSame('custom.event', $event->getName());
    }

    public function testSetNameOverridesProvidedName(): void
    {
        $event = new Event('original.event');
        $event->setName('updated.event');
        self::assertSame('updated.event', $event->getName());
    }

    public function testGetTargetReturnsNullByDefault(): void
    {
        $event = new Event();
        self::assertNull($event->getTarget());
    }

    public function testGetTargetReturnsProvidedTarget(): void
    {
        $target = new \stdClass();
        $event  = new Event(null, $target);
        self::assertSame($target, $event->getTarget());
    }

    public function testSetTargetUpdatesTarget(): void
    {
        $target  = new \stdClass();
        $target2 = new \stdClass();

        $event = new Event(null, $target);
        $event->setTarget($target2);

        self::assertSame($target2, $event->getTarget());
    }

    public function testGetParamsReturnsEmptyArrayByDefault(): void
    {
        $event = new Event();
        self::assertSame([], $event->getParams());
    }

    public function testGetParamsReturnsProvidedParams(): void
    {
        $params = ['key' => 'value'];
        $event  = new Event(null, null, $params);
        self::assertSame($params, $event->getParams());
    }

    public function testSetParamAddsValue(): void
    {
        $event = new Event();
        $event->setParam('foo', 'bar');
        self::assertSame('bar', $event->getParam('foo'));
    }

    public function testGetParamReturnsDefaultWhenMissing(): void
    {
        $event = new Event();
        self::assertNull($event->getParam('nonexistent'));
        self::assertSame('fallback', $event->getParam('nonexistent', 'fallback'));
    }

    public function testSetParamsReplacesAllParams(): void
    {
        $event = new Event(null, null, ['old' => 'value']);
        $event->setParams(['new' => 'params']);
        self::assertSame(['new' => 'params'], $event->getParams());
    }

    public function testPropagationStoppedDefaultsToFalse(): void
    {
        $event = new Event();
        self::assertFalse($event->isPropagationStopped());
    }

    public function testStopPropagation(): void
    {
        $event = new Event();
        $event->stopPropagation();
        self::assertTrue($event->isPropagationStopped());
    }

    public function testStopPropagationWithFalse(): void
    {
        $event = new Event();
        $event->stopPropagation();
        $event->stopPropagation(false);
        self::assertFalse($event->isPropagationStopped());
    }
}
