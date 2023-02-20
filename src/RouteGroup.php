<?php

declare(strict_types=1);

namespace Remcosmits\Framework\Routing;

use Closure;
use Remcosmits\Framework\Routing\Interface\MiddlewareInterface;
use Remcosmits\Framework\Routing\Interface\RouterInterface;

final class RouteGroup
{
    /** @var array<int, class-string<MiddlewareInterface>> */
    private array $middlewares = [];

    private string $prefix = '';

    private string $name = '';

    public function __construct(
        private readonly RouterInterface $router,
        private readonly Closure $callback
    ) {
    }

    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

    public function prefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function middleware(string $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /** @return array<int, class-string<MiddlewareInterface>> */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getCallback(): Closure
    {
        return $this->callback;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __destruct()
    {
        // get current route group attributes
        $router = $this->getRouter();

        $oldMiddlewares = $router->getMiddlewares();
        $oldPrefix = $router->getPrefix();

        $router->setMiddlewares(...$this->getMiddlewares());
        $router->setPrefix(
            rtrim(
                preg_replace('/\/+/', '/', '/' . $oldPrefix . '/' . $this->getPrefix()),
                '/'
            )
        );

        ($this->getCallback())(); // apply route group attributes

        // revert route group attributes (step 1)
        $router->setMiddlewares(...$oldMiddlewares);
        $router->setPrefix($oldPrefix);
    }
}
