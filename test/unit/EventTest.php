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

namespace WebwareTest\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Webware\Event\Event;
use Webware\Event\EventPropagationTrait;

#[CoversClass(Event::class)]
#[CoversTrait(EventPropagationTrait::class)]
#[CoversMethod(Event::class, 'getName')]
#[CoversMethod(Event::class, 'setName')]
#[CoversMethod(Event::class, 'getTarget')]
#[CoversMethod(Event::class, 'setTarget')]
#[CoversMethod(Event::class, 'getParam')]
#[CoversMethod(Event::class, 'setParam')]
#[CoversMethod(Event::class, 'getParams')]
#[CoversMethod(Event::class, 'setParams')]
final class EventTest extends TestCase
{
    #[Test]
    public function getNameReturnsClassNameByDefault(): void
    {
        $event = new Event();
        static::assertSame(Event::class, $event->getName());
    }

    #[Test]
    public function getNameReturnsProvidedName(): void
    {
        $event = new Event('test.event');
        static::assertSame('test.event', $event->getName());
    }

    #[Test]
    public function getParamReturnsDefaultWhenMissing(): void
    {
        $event = new Event();
        static::assertNull($event->getParam('nonexistent'));
        static::assertSame('fallback', $event->getParam('nonexistent', 'fallback'));
    }

    #[Test]
    public function getParamsReturnsEmptyArrayByDefault(): void
    {
        $event = new Event();
        static::assertSame([], $event->getParams());
    }

    #[Test]
    public function getParamsReturnsProvidedParams(): void
    {
        $params = ['key' => 'value'];
        $event  = new Event(null, null, $params);
        static::assertSame($params, $event->getParams());
    }

    #[Test]
    public function getTargetReturnsNullByDefault(): void
    {
        $event = new Event();
        static::assertNull($event->getTarget());
    }

    #[Test]
    public function getTargetReturnsProvidedTarget(): void
    {
        $target = new stdClass();
        $event  = new Event(null, $target);
        static::assertSame($target, $event->getTarget());
    }

    #[Test]
    public function propagationStoppedDefaultsToFalse(): void
    {
        $event = new Event();
        static::assertFalse($event->isPropagationStopped());
    }

    #[Test]
    public function setNameOverridesDefault(): void
    {
        $event = new Event();
        $event->setName('custom.event');
        static::assertSame('custom.event', $event->getName());
    }

    #[Test]
    public function setNameOverridesProvidedName(): void
    {
        $event = new Event('original.event');
        $event->setName('updated.event');
        static::assertSame('updated.event', $event->getName());
    }

    #[Test]
    public function setParamAddsValue(): void
    {
        $event = new Event();
        $event->setParam('foo', 'bar');
        static::assertSame('bar', $event->getParam('foo'));
    }

    #[Test]
    public function setParamsReplacesAllParams(): void
    {
        $event = new Event(null, null, ['old' => 'value']);
        $event->setParams(['new' => 'params']);
        static::assertSame(['new' => 'params'], $event->getParams());
    }

    #[Test]
    public function setTargetUpdatesTarget(): void
    {
        $target  = new stdClass();
        $target2 = new stdClass();

        $event = new Event(null, $target);
        $event->setTarget($target2);

        static::assertSame($target2, $event->getTarget());
    }

    #[Test]
    public function stopPropagation(): void
    {
        $event = new Event();
        $event->stopPropagation();
        static::assertTrue($event->isPropagationStopped());
    }

    #[Test]
    public function stopPropagationWithFalse(): void
    {
        $event = new Event();
        $event->stopPropagation();
        $event->stopPropagation(false);
        static::assertFalse($event->isPropagationStopped());
    }
}
