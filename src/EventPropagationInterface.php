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
 * @api
 */
interface EventPropagationInterface
{
    public function isPropagationStopped(): bool;

    // Deliberately a flag-bearing signature: this contract mirrors
    // Laminas\EventManager\EventInterface::stopPropagation(bool $flag = true),
    // and PSR-14 declares no stop method at all — its StoppableEventInterface
    // carries only isPropagationStopped(). Reshaping it to satisfy the linter
    // would change the contract every consumer adopts, so it stands as is.
    // @mago-expect lint:no-boolean-flag-parameter
    public function stopPropagation(bool $flag = true): void;
}
