<?php

declare(strict_types=1);

namespace Remcosmits\Framework\Routing\Exception;

use Remcosmits\Framework\Http\Exception\HttpNotAuthorisedException;

final class MiddlewareFailedException extends HttpNotAuthorisedException
{
}
