<?php

declare(strict_types=1);

namespace Remcosmits\Framework\Routing\Interface;

use Countable;
use IteratorAggregate;
use Remcosmits\Framework\Routing\Exception\RouteNotFoundException;

interface RouteCollectionInterface extends Countable, IteratorAggregate
{
    /**
     * Add Route, you may split routes by their request method here
     */
    public function addRoute(RouteInterface $route): void;

    /**
     * Find route by his name that are registered
     *
     * @throws RouteNotFoundException
     */
    public function findRouteByName(string $name): RouteInterface;

    /**
     * Get all routes by one request method that are registered
     *
     * @return RouteInterface[]
     */
    public function getRoutesByMethod(string $method): array;

    /**
     * Get all named routes that are registered
     *
     * @return array<string, RouteInterface>
     */
    public function getNamedRoutes(): array;

    /**
     * Get all the routes that are registered
     *
     * @return RouteInterface[]
     */
    public function getAllRoutes(): array;
}
