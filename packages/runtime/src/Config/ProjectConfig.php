<?php

declare(strict_types=1);

namespace PhpWails\Runtime\Config;

use InvalidArgumentException;

final readonly class ProjectConfig
{
    public function __construct(public int $schemaVersion, public array $app, public array $window, public array $frontend, public array $capabilities = []) {}
    public static function fromFile(string $path): self
    {
        $root = realpath(dirname($path));
        $data = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
        foreach (['schemaVersion', 'app', 'window', 'frontend'] as $key) if (!array_key_exists($key, $data)) throw new InvalidArgumentException("Missing configuration key: {$key}");
        if (!is_int($data['schemaVersion']) || $data['schemaVersion'] < 1) throw new InvalidArgumentException('schemaVersion must be a positive integer.');
        if (isset($data['frontend']['devCommand']) && !is_array($data['frontend']['devCommand'])) throw new InvalidArgumentException('frontend.devCommand must be an argument array.');
        return new self($data['schemaVersion'], $data['app'], $data['window'], $data['frontend'], $data['capabilities'] ?? []);
    }
}
