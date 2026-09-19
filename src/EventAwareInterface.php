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

/**
 * This file is part of the Webware Event package.
 *
 * Copyright (c) 2026 Joey (aka Tyrsson) Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
interface EventAwareInterface
{
    /**
     * The event that triggered this object, or null before one is set.
     *
     * Nullable because `EventAwareTrait` initialises the property to null, so
     * null is reachable until `setEvent()` is called. Declaring this
     * non-nullable made the interface and its own trait mutually unusable:
     * a trait method returning `?EventInterface` widens the declared return
     * type, which is a PHP fatal error when a class combines the two.
     */
    public function getEvent(): ?EventInterface;

    public function setEvent(EventInterface $event): void;
}
