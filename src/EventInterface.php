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
interface EventInterface
{
    public function getName(): string;

    public function getParam(string $name, mixed $default = null): mixed;

    /**
     * @return array<array-key, mixed>
     */
    public function getParams(): array;

    public function getTarget(): ?object;

    public function setName(string $name): void;

    public function setParam(string $name, mixed $value): void;

    /**
     * @param array<array-key, mixed> $params
     */
    public function setParams(array $params): void;

    public function setTarget(object $target): void;
}
