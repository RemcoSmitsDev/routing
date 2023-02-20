<?php

declare(strict_types=1);

namespace Remcosmits\Framework\Routing;

use ArrayIterator;
use Remcosmits\Framework\Routing\Exception\RouteNotFoundException;
use Remcosmits\Framework\Routing\Interface\RouteCollectionInterface;
use Remcosmits\Framework\Routing\Interface\RouteInterface;
use Traversable;

final class RouteCollection implements RouteCollectionInterface
{
    /**
     * Array of all the routes
     *
     * @var RouteInterface[]
     */
    private array $allRoutes = [];

    /**
     * Array of all the named routes keys are the names
     *
     * @var array<string, RouteInterface>
     */
    private array $namedRoutes = [];

    /**
     * Array of all routes by request method as key
     *
     * @var array<string, RouteInterface[]>
     */
    private array $routesByMethod = [];

    /** @inheritDoc */
    public function addRoute(RouteInterface $route): void
    {
        if (empty($route->getName()) === false) {
            $this->namedRoutes[$route->getName()] = $route;
        }

        foreach ($route->getMethods() as $method) {
            $this->routesByMethod[$method][] = $route;
        }

        $this->allRoutes[] = $route;
    }

    /** @inheritDoc */
    public function findRouteByName(string $name): RouteInterface
    {
        if (array_key_exists($name, $this->getNamedRoutes())) {
            return $this->getNamedRoutes()[$name];
        }

        throw new RouteNotFoundException();
    }

    /** @inheritDoc */
    public function getRoutesByMethod(string $method): array
    {
        return $this->routesByMethod[$method] ?? [];
    }

    /** @inheritDoc */
    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }

    /** @inheritDoc */
    public function getAllRoutes(): array
    {
        return $this->allRoutes;
    }

    /** @return Traversable<int, RouteInterface> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->getAllRoutes());
    }

    /** @return positive-int */
    public function count(): int
    {
        return count($this->getAllRoutes());
    }
}
