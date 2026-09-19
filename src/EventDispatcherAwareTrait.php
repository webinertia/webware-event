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

use Override;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Requires any class using this trait to also implement the paired
 * {@see EventDispatcherAwareInterface} — mago reports `missing-required-interface` otherwise.
 *
 * @api
 *
 * @require-implements EventDispatcherAwareInterface
 */
trait EventDispatcherAwareTrait
{
    protected EventDispatcherInterface $eventDispatcher;

    #[Override]
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    #[Override]
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }
}
