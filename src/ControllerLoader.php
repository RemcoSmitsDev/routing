<?php

declare(strict_types=1);

namespace Remcosmits\Framework\Routing;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Remcosmits\Framework\Facade\Log;
use Remcosmits\Framework\Resolver\DependencyInjector;
use Remcosmits\Framework\Routing\Attribute\Route as RouteAttribute;
use Remcosmits\Framework\Routing\Interface\RouterInterface;
use Spatie\StructureDiscoverer\Discover;
use Throwable;

final readonly class ControllerLoader
{
    public function __construct(
        private string $path,
        private RouterInterface $router,
    ) {
    }

    /** @throws ReflectionException */
    public function load(): void
    {
        foreach (Discover::in($this->path)->get() as $controller) {
            $controller = new ReflectionClass($controller);

            foreach ($controller->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $this->loadRouteFromMethod($controller, $method);
            }
        }
    }

    private function loadRouteFromMethod(ReflectionClass $controller, ReflectionMethod $method): void
    {
        $route = $method->getAttributes(RouteAttribute::class);

        if (empty($route[0])) {
            return;
        }

        try {
            $this->router->addRoute(
                DependencyInjector::resolve(
                    Route::class,
                    $route[0]->getArguments() + ['action' => [$controller->getName(), $method->getName()]]
                )
            );
        } catch (Throwable $throwable) {
            Log::debug(
                sprintf(
                    'Failed to load [%s -> %s] reason: %s',
                    $controller->getName(),
                    $method->getName(),
                    $throwable->getMessage()
                ),
                $route[0]->getArguments()
            );
        }
    }
}
