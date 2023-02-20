<?php

declare(strict_types=1);

namespace Remcosmits\Framework\Routing;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;
use Remcosmits\Framework\Http\Response\Response;
use Remcosmits\Framework\Routing\Interface\RouteInterface;
use Throwable;

final class Route implements RouteInterface
{
    private string $pathRegex;

    private bool $isDynamicRoute;

    public function __construct(
        private readonly string $path,
        private readonly SerializableClosure|array $action,
        private readonly array $methods = ['HEAD', 'GET'],
        private array $middlewares = [],
        private ?string $name = null,
        private array $patterns = [],
        private array $attributes = []
    ) {
        $this->pathRegex = $this->formatPathRegex();
        $this->isDynamicRoute = str_contains($this->getPath(), '{') && str_contains($this->getPath(), '}');
    }

    /** @inheritDoc */
    public function addMiddleware(string ...$middleware): RouteInterface
    {
        $this->middlewares = array_unique(array_merge($this->getMiddlewares(), $middleware));

        return $this;
    }

    /** @inheritDoc */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /** @inheritDoc */
    public function setName(string $name): RouteInterface
    {
        $this->name = $name;

        return $this;
    }

    /** @inheritDoc */
    public function getName(): ?string
    {
        return $this->name;
    }

    /** @inheritDoc */
    public function addPattern(string $name, mixed $value): RouteInterface
    {
        $this->patterns = array_merge($this->getPatterns(), [$name => $value]);

        return $this;
    }

    /** @inheritDoc */
    public function addPatterns(array $patterns): RouteInterface
    {
        $this->patterns = array_merge($this->getPatterns(), $patterns);

        return $this;
    }

    /** @inheritDoc */
    public function getPatterns(): array
    {
        return $this->patterns;
    }

    /** @inheritDoc */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /** @inheritDoc */
    public function getAction(): Closure|array
    {
        if ($this->action instanceof SerializableClosure) {
            try {
                return $this->action->getClosure();
            } catch (Throwable) {
                return static fn() => new Response();
            }
        }

        return $this->action;
    }

    /** @inheritDoc */
    public function getPath(): string
    {
        return $this->path;
    }

    /** @inheritDoc */
    public function getPathRegex(): string
    {
        return $this->pathRegex;
    }

    private function formatPathRegex(): string
    {
        return preg_replace_callback(
            '/\{[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+\}/',
            function (array $match) {
                $dynamicPlaceholder = trim($match[0], '{}');

                return sprintf(
                    '(?<%s>%s)',
                    $dynamicPlaceholder,
                    $this->getPatterns()[$dynamicPlaceholder] ?? '[^/]+'
                );
            },
            $this->getPath()
        );
    }

    /** @inheritDoc */
    public function isDynamicRoute(): bool
    {
        return $this->isDynamicRoute;
    }

    /** @inheritDoc */
    public function addAttribute(string $name, mixed $value): RouteInterface
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /** @inheritDoc */
    public function addAttributes(array $attributes): RouteInterface
    {
        $this->attributes = array_merge($this->getAttributes(), $attributes);

        return $this;
    }

    /** @inheritDoc */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
