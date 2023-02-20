<?php

declare(strict_types=1);

namespace Remcosmits\Framework\Routing\Interface;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Remcosmits\Framework\Routing\Exception\RouteNotFoundException;
use Remcosmits\Framework\Routing\RouteGroup;

interface RouterInterface
{
    public function getContainer(): ContainerInterface;

    public function addRoute(RouteInterface $route): void;

    public function getRoutes(): RouteCollectionInterface;

    public function setPrefix(string $prefix): void;

    public function getPrefix(): string;

    /** @param class-string<MiddlewareInterface> ...$middleware */
    public function setMiddlewares(string ...$middleware): void;

    /** @return array<int, class-string<MiddlewareInterface>> */
    public function getMiddlewares(): array;

    /**
     * Validate the middlewares that were set on the current route
     */
    public function validateMiddlewares(RouteInterface $route, ServerRequestInterface $request): RouteInterface;

    /**
     * Get current route that was found with the findRouteByUri method
     */
    public function getCurrentRoute(): RouteInterface;

    /**
     * Sets the current route that was found with the findRouteByUri method
     */
    public function setCurrentRoute(RouteInterface $route): void;

    /**
     * Handles the incoming request and finds the route that belongs to the request uri
     */
    public function handle(ServerRequestInterface $request): ResponseInterface;

    /**
     * Load routes from cache or from routes file or controllers
     */
    public function loadRoutes(): RouterInterface;

    /** @throws RouteNotFoundException */
    public function findRouteByUri(UriInterface $uri, string $method): RouteInterface;

    /** @param Closure():ResponseInterface|array{0: class-string, 1: string}|class-string $action */
    public function get(string $path, Closure|array|string $action): RouteInterface;

    /** @param Closure():ResponseInterface|array{0: class-string, 1: string}|class-string $action */
    public function post(string $path, Closure|array|string $action): RouteInterface;

    /** @param Closure():ResponseInterface|array{0: class-string, 1: string}|class-string $action */
    public function put(string $path, Closure|array|string $action): RouteInterface;

    /** @param Closure():ResponseInterface|array{0: class-string, 1: string}|class-string $action */
    public function patch(string $path, Closure|array|string $action): RouteInterface;

    /** @param Closure():ResponseInterface|array{0: class-string, 1: string}|class-string $action */
    public function delete(string $path, Closure|array|string $action): RouteInterface;

    /** @param Closure():ResponseInterface|array{0: class-string, 1: string}|class-string $action */
    public function options(string $path, Closure|array|string $action): RouteInterface;

    /** @param Closure():ResponseInterface|array{0: class-string, 1: string}|class-string $action */
    public function any(string $path, Closure|array|string $action): RouteInterface;

    /**
     * @param string[]|string $methods
     * @param Closure():ResponseInterface|array{0: class-string, 1: string}|class-string $action
     */
    public function match(string $path, Closure|array|string $action, array|string $methods = []): RouteInterface;

    /**
     * Group routes with prefix/middlewares
     */
    public function group(Closure $closure): RouteGroup;
}
