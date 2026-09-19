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
 * A listener for an event of this package.
 *
 * The package owns both halves of the event contract. Until this interface
 * moved here, the event contract lived in this package while the only listener
 * contract lived in messagebus-event, which left the family split across two
 * packages and forced consumers of a listener to depend on the bus. See
 * webinertia/webware-tools#21.
 *
 * This is the webware listener contract, not a PSR-14 type: PSR-14 defines no
 * listener interface, only `ListenerProviderInterface` returning callables.
 *
 * @api
 */
interface ListenerInterface
{
    public function __invoke(EventInterface $event): void;
}
