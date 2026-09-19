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

use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Listener provider used to exercise the `listener_providers` config key.
 *
 * Records the events it is asked about, then hands back a listener that records
 * what it was dispatched.
 */
final class RecordingListenerProvider implements ListenerProviderInterface
{
    /** @var list<class-string> */
    public array $queried = [];

    /** @var list<class-string> */
    public array $handled = [];

    /**
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        $this->queried[] = $event::class;

        return [
            function (object $dispatched): void {
                $this->handled[] = $dispatched::class;
            },
        ];
    }
}
