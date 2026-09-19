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
use PHPUnit\Framework\TestCase;
use Webware\Event\Event;
use Webware\Event\EventAwareInterface;
use Webware\Event\EventAwareTrait;

#[CoversClass(EventAwareTrait::class)]
#[CoversMethod(EventAwareTrait::class, 'getEvent')]
#[CoversMethod(EventAwareTrait::class, 'setEvent')]
final class EventAwareTest extends TestCase
{
    public function testGetEventIsNullBeforeAnEventIsSet(): void
    {
        self::assertNull($this->createSubject()->getEvent());
    }

    /**
     * The pairing is the assertion: a class declaring the interface and using
     * the trait did not compile while the interface declared a non-nullable
     * return, because the trait widens it to `?EventInterface`.
     */
    public function testInterfaceAndTraitCanBeCombined(): void
    {
        $subject = $this->createSubject();

        self::assertInstanceOf(EventAwareInterface::class, $subject);
    }

    public function testSetEventMakesItAvailable(): void
    {
        $event   = new Event();
        $subject = $this->createSubject();

        $subject->setEvent($event);

        self::assertSame($event, $subject->getEvent());
    }

    private function createSubject(): EventAwareInterface
    {
        return new class() implements EventAwareInterface {
            use EventAwareTrait;
        };
    }
}
