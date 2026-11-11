<?php

declare(strict_types=1);

namespace PhpWails\Runtime\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Export
{
    public function __construct(public readonly ?string $name = null) {}
}
