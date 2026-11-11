<?php

declare(strict_types=1);

namespace PhpWails\Runtime\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class Bind
{
    public function __construct(public readonly ?string $name = null) {}
}
