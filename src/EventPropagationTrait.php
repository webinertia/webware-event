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

/**
 * Requires any class using this trait to also implement the paired
 * {@see EventPropagationInterface} — mago reports `missing-required-interface` otherwise.
 *
 * @api
 *
 * @require-implements EventPropagationInterface
 */
trait EventPropagationTrait
{
    protected bool $propagationStopped = false;

    #[Override]
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    #[Override]
    public function stopPropagation(bool $flag = true): void
    {
        $this->propagationStopped = $flag;
    }
}
