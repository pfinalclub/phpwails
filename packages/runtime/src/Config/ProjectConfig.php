<?php

declare(strict_types=1);

namespace PhpWails\Runtime\Config;

use InvalidArgumentException;

final readonly class ProjectConfig
{
    public function __construct(public int $schemaVersion, public array $app, public array $window, public array $frontend, public array $capabilities = [], public array $build = [], public string $projectRoot = '') {}
    public static function fromFile(string $path): self
    {
        if (!is_file($path)) throw new InvalidArgumentException("Configuration file not found: {$path}");
        $projectRoot = realpath(dirname($path));
        $data = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($data)) throw new InvalidArgumentException('Configuration must be an object.');
        foreach (['schemaVersion', 'app', 'window', 'frontend'] as $key) if (!array_key_exists($key, $data)) throw new InvalidArgumentException("Missing configuration key: {$key}");
        if (!is_int($data['schemaVersion']) || $data['schemaVersion'] < 1) throw new InvalidArgumentException('schemaVersion must be a positive integer.');
        foreach (['app', 'window', 'frontend'] as $key) if (!is_array($data[$key])) throw new InvalidArgumentException("{$key} must be an object.");
        if (!isset($data['app']['id'], $data['app']['name'], $data['app']['version'])) throw new InvalidArgumentException('app.id, app.name and app.version are required.');
        if (!is_string($data['app']['id']) || !preg_match('/^[A-Za-z0-9.-]+$/', $data['app']['id'])) throw new InvalidArgumentException('app.id must be a bundle identifier-like string.');
        if (isset($data['frontend']['devCommand']) && !is_array($data['frontend']['devCommand'])) throw new InvalidArgumentException('frontend.devCommand must be an argument array.');
        if (isset($data['frontend']['buildCommand']) && !is_array($data['frontend']['buildCommand'])) throw new InvalidArgumentException('frontend.buildCommand must be an argument array.');
        foreach (['width', 'height'] as $key) if (isset($data['window'][$key]) && (!is_int($data['window'][$key]) || $data['window'][$key] <= 0)) throw new InvalidArgumentException("window.{$key} must be a positive integer.");
        $build = $data['build'] ?? [];
        if (!is_array($build)) throw new InvalidArgumentException('build must be an object.');
        if (isset($build['outputDir']) && !is_string($build['outputDir'])) throw new InvalidArgumentException('build.outputDir must be a path string.');
        return new self($data['schemaVersion'], $data['app'], $data['window'], $data['frontend'], $data['capabilities'] ?? [], $build, $projectRoot ?: dirname($path));
    }
}
