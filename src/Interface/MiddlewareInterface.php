<?php

declare(strict_types=1);

namespace Remcosmits\Framework\Routing\Interface;

use Psr\Http\Message\ServerRequestInterface;
use Remcosmits\Framework\Http\Exception\HttpException;

interface MiddlewareInterface
{
    /**
     * Return true if the middleware is valid
     *
     * @throws HttpException
     */
    public function handle(ServerRequestInterface $request): bool;
}
