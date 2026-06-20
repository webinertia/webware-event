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

namespace Webware\Event\Middleware;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class EventDispatcherMiddleware implements MiddlewareInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle(
            $request->withAttribute(
                EventDispatcherInterface::class,
                $this->eventDispatcher
            )
        );
    }
}
