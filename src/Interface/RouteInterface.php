<?php

declare(strict_types=1);

namespace Remcosmits\Framework\Routing\Interface;

use Closure;
use Psr\Http\Message\ResponseInterface;

interface RouteInterface
{
    /**
     * Add a middleware to the route
     *
     * @param class-string<MiddlewareInterface> $middleware
     */
    public function addMiddleware(string $middleware): RouteInterface;

    /**
     * Retrieve all the middlewares from the route
     *
     * @return array<int, class-string<MiddlewareInterface>>
     */
    public function getMiddlewares(): array;

    /**
     * Set the name of the route
     */
    public function setName(string $name): RouteInterface;

    /**
     * Retrieve the name of the route
     */
    public function getName(): ?string;

    /**
     * Retrieve the action that will be called when the route is found
     *
     * @return Closure():ResponseInterface|array{0: class-string, 1: string}
     */
    public function getAction(): Closure|array;

    /**
     * Retrieve the path including the dynamic params
     */
    public function getPath(): string;

    /**
     * Retrieve the path in regex format,
     * You have to replace the dynamic params here
     */
    public function getPathRegex(): string;

    /**
     * Returns true if a route path contains a dynamic param
     */
    public function isDynamicRoute(): bool;

    /**
     * Returns all request methods that the route allows
     *
     * @return string[]
     */
    public function getMethods(): array;

    /**
     * Set regex pattern for the dynamic params
     */
    public function addPattern(string $name, mixed $value): RouteInterface;

    /**
     * Set regex patterns for the dynamic properties
     *
     * @param string[] $patterns
     */
    public function addPatterns(array $patterns): RouteInterface;

    /**
     * Get all the regex patterns that you can apply inside the getPathRegex method
     *
     * @return string[]
     */
    public function getPatterns(): array;

    /**
     * Add attribute to the route,
     * An attribute can contain any value
     */
    public function addAttribute(string $name, mixed $value): RouteInterface;

    /**
     * Add multiple attributes to the route
     *
     * @param array<string, mixed> $attributes
     */
    public function addAttributes(array $attributes): RouteInterface;

    /**
     * Retrieve all the attributes from the route
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array;
}
