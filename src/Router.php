<?php

declare(strict_types=1);

namespace Remcosmits\Framework\Routing;

use Closure;
use Laravel\SerializableClosure\Exceptions\PhpVersionNotSupportedException;
use Laravel\SerializableClosure\SerializableClosure;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use ReflectionException;
use Remcosmits\Framework\Cache\CacheItem;
use Remcosmits\Framework\Cache\Interface\CacheInterface;
use Remcosmits\Framework\Event\BeforeResponseSendEvent;
use Remcosmits\Framework\Event\RouteFoundEvent;
use Remcosmits\Framework\Http\Exception\HttpNotFoundException;
use Remcosmits\Framework\Http\Response\Response;
use Remcosmits\Framework\Resolver\DependencyInjector;
use Remcosmits\Framework\Routing\Exception\MiddlewareFailedException;
use Remcosmits\Framework\Routing\Exception\RouteNotFoundException;
use Remcosmits\Framework\Routing\Interface\MiddlewareInterface;
use Remcosmits\Framework\Routing\Interface\RouteCollectionInterface;
use Remcosmits\Framework\Routing\Interface\RouteInterface;
use Remcosmits\Framework\Routing\Interface\RouterInterface;
use RuntimeException;

class_exists(DependencyInjector::class) === false && throw new RuntimeException(
    'Class [' . DependencyInjector::class . "] doesn't exists"
);

final class Router implements RouterInterface
{
    /** @var array<int, class-string<MiddlewareInterface>> */
    private array $middlewares = [];

    private string $prefix = '';

    private RouteInterface $currentRoute;

    public function __construct(
        private readonly ContainerInterface $container,
        private ?RouteCollectionInterface $routes = null
    ) {
        $this->routes ??= new RouteCollection();
    }

    public function getRoutes(): RouteCollectionInterface
    {
        return $this->routes;
    }

    public function addRoute(RouteInterface $route): void
    {
        $this->getRoutes()->addRoute($route);
    }

    /** @return array<int, class-string<MiddlewareInterface>> */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /** @param class-string<MiddlewareInterface> ...$middleware */
    public function setMiddlewares(string ...$middleware): void
    {
        $this->middlewares = $middleware;
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /** @inheritDoc */
    public function get(string $path, Closure|array|string $action): RouteInterface
    {
        return $this->match($path, $action, ['HEAD', 'GET']);
    }

    /** @inheritDoc */
    public function post(string $path, Closure|array|string $action): RouteInterface
    {
        return $this->match($path, $action, 'POST');
    }

    /** @inheritDoc */
    public function put(string $path, Closure|array|string $action): RouteInterface
    {
        return $this->match($path, $action, 'PUT');
    }

    /** @inheritDoc */
    public function patch(string $path, Closure|array|string $action): RouteInterface
    {
        return $this->match($path, $action, 'PATCH');
    }

    /** @inheritDoc */
    public function delete(string $path, Closure|array|string $action): RouteInterface
    {
        return $this->match($path, $action, 'DELETE');
    }

    /** @inheritDoc */
    public function options(string $path, Closure|array|string $action): RouteInterface
    {
        return $this->match($path, $action, 'OPTIONS');
    }

    /** @inheritDoc */
    public function any(string $path, Closure|array|string $action): RouteInterface
    {
        return $this->match($path, $action, ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']);
    }

    /** @inheritDoc */
    public function match(string $path, Closure|array|string $action, array|string $methods = []): RouteInterface
    {
        $route = $this->createRoute($path, $action, $methods);

        $this->addRoute($route);

        return $route;
    }

    /** @throws PhpVersionNotSupportedException */
    private function createRoute(string $path, Closure|array|string $action, array|string $methods): RouteInterface
    {
        $methods = array_map('strtoupper', (array)$methods);

        if (is_string($action)) {
            $action = [$action, '__invoke'];
        }

        if (in_array('GET', $methods, true) && in_array('HEAD', $methods, true) === false) {
            $methods[] = 'HEAD';
        }

        return new Route(
            preg_replace(
                '/\/+/',
                '/',
                '/' . trim($this->getPrefix(), '/') . '/' . ltrim($path, '/'),
            ),
            is_array($action) ? $action : new SerializableClosure($action),
            $methods,
            $this->getMiddlewares()
        );
    }

    public function group(Closure $closure): RouteGroup
    {
        return new RouteGroup($this, $closure);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function validateMiddlewares(RouteInterface $route, ServerRequestInterface $request): RouteInterface
    {
        foreach ($route->getMiddlewares() as $middleware) {
            // resolve middleware
            if ($this->getContainer()->get($middleware)->handle($request) === false) {
                throw new MiddlewareFailedException();
            }
        }

        return $route;
    }

    public function findRouteByUri(UriInterface $uri, string $method): RouteInterface
    {
        // format request path
        $requestPath = rtrim($uri->getPath(), '/');

        // get routes by request method
        foreach ($this->getRoutes()->getRoutesByMethod($method) as $route) {
            // when route doesn't have dynamic params
            if ($route->isDynamicRoute() === false) {
                if ($route->getPath() === $requestPath) {
                    return $route;
                }

                continue;
            }

            // check if route path matches request path
            if (preg_match("#^{$route->getPathRegex()}$#", $requestPath, $attributes)) {
                return $route->addAttributes(
                    array_filter(
                        $attributes,
                        static fn(string $key) => is_numeric($key) === false,
                        ARRAY_FILTER_USE_KEY
                    )
                );
            }
        }

        throw new HttpNotFoundException();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws RouteNotFoundException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $route = $this->findRouteByUri($request->getUri(), $request->getMethod());

        $route = $this->getContainer()->get(EventDispatcherInterface::class)->dispatch(
            new RouteFoundEvent($route)
        )->getRoute();

        $route = $this->validateMiddlewares($route, $request);

        $response = DependencyInjector::resolve($route->getAction(), $route->getAttributes());

        if ($response instanceof ResponseInterface === false) {
            $response = new Response($response);
        }

        return $this->getContainer()->get(EventDispatcherInterface::class)->dispatch(
            new BeforeResponseSendEvent($response)
        )->getResponse();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function loadRoutes(): RouterInterface
    {
        /** @var CacheInterface $cache */
        $cache = $this->getContainer()->get(CacheInterface::class);

        $routesFile = dirname(__DIR__, 2) . '/tests/routes.php';

        $cacheItem = $cache->getItem('_routes');

        $controllerPath = realpath(__DIR__ . '/../_Controller/');

        if (
            $cacheItem->isHit() &&
            $cacheItem->getCreatedAt()->getTimestamp() > filemtime($routesFile) &&
            $cacheItem->getCreatedAt()->getTimestamp() > filemtime($controllerPath)
        ) {
            $this->routes = unserialize(
                $cacheItem->get()
            );
        } else {
            $controllerLoader = new ControllerLoader($controllerPath, $this);
            $controllerLoader->load();

            require_once($routesFile);

            $cache->saveDeferred(
                new CacheItem('_routes', serialize($this->getRoutes()))
            );
        }

        return $this;
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function getCurrentRoute(): RouteInterface
    {
        return $this->currentRoute;
    }

    public function setCurrentRoute(RouteInterface $route): void
    {
        $this->currentRoute = $route;
    }

    public function __call(string $name, array $arguments): RouteInterface|RouteGroup
    {
        return call_user_func_array([Route::class, $name], $arguments);
    }
}
