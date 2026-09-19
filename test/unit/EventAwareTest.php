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

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webware\Event\Event;
use Webware\Event\EventAwareInterface;
use Webware\Event\EventAwareTrait;

#[CoversTrait(EventAwareTrait::class)]
final class EventAwareTest extends TestCase
{
    #[Test]
    public function getEventIsNullBeforeAnEventIsSet(): void
    {
        static::assertNull($this->createSubject()->getEvent());
    }

    /**
     * The pairing is the assertion: a class declaring the interface and using
     * the trait did not compile while the interface declared a non-nullable
     * return, because the trait widens it to `?EventInterface`.
     */
    #[Test]
    public function interfaceAndTraitCanBeCombined(): void
    {
        $subject = $this->createSubject();

        static::assertInstanceOf(EventAwareInterface::class, $subject);
    }

    #[Test]
    public function setEventMakesItAvailable(): void
    {
        $event   = new Event();
        $subject = $this->createSubject();

        $subject->setEvent($event);

        static::assertSame($event, $subject->getEvent());
    }

    private function createSubject(): EventAwareInterface
    {
        return new class() implements EventAwareInterface {
            use EventAwareTrait;
        };
    }
}
