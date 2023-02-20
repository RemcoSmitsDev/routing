<?php

declare(strict_types=1);

namespace Remcosmits\Framework\Routing\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Remcosmits\Framework\Routing\Interface\MiddlewareInterface;

final class TestMiddleware implements MiddlewareInterface
{
    /** @inheritDoc */
    public function handle(ServerRequestInterface $request): bool
    {
        return true;
    }
}
