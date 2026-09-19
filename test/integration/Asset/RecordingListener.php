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

namespace WebwareTestIntegration\Event\Asset;

use Webware\Event\Event;

/**
 * Listener used to exercise the class-string listener spec.
 *
 * Reached through `LazyListener`, so it is resolved from the container rather
 * than constructed by the aggregate.
 */
final class RecordingListener
{
    /** @var list<class-string> */
    public array $handled = [];

    public function __invoke(Event $event): void
    {
        $this->handled[] = $event->getName();
    }
}
