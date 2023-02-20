<?php

declare(strict_types=1);

namespace Remcosmits\Framework\Routing\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Route
{
    public function __construct(
        private string $path,
        private array $methods = ['HEAD', 'GET'],
        private array $middlewares = [],
        private ?string $name = null,
        private array $patterns = [],
        private array $attributes = []
    ) {
    }
}
