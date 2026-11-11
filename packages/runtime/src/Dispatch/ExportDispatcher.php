<?php

declare(strict_types=1);
namespace PhpWails\Runtime\Dispatch;

use PhpWails\Runtime\Attribute\Export;
use PhpWails\Runtime\Error\ErrorEnvelope;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

final class ExportDispatcher
{
    /** @var array<string, array{object: object, method: ReflectionMethod}> */
    private array $methods = [];

    public function register(object $service, ?string $prefix = null): void
    {
        $reflection = new ReflectionClass($service);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(Export::class);
            if ($attributes === []) continue;
            $attribute = $attributes[0]->newInstance();
            $name = $attribute->name ?? $method->getName();
            $name = ($prefix ? trim($prefix, '.') . '.' : '') . $name;
            if (isset($this->methods[$name])) throw new \InvalidArgumentException("Duplicate exported method: {$name}");
            $this->methods[$name] = ['object' => $service, 'method' => $method];
        }
    }

    public function invoke(string $name, array $params = []): array
    {
        $entry = $this->methods[$name] ?? null;
        if ($entry === null) return (new ErrorEnvelope('rpc.method_not_found', 'not-found', "Unknown method: {$name}"))->jsonSerialize();
        try {
            $arguments = $this->arguments($entry['method'], $params);
            return ['ok' => true, 'result' => $entry['method']->invokeArgs($entry['object'], $arguments)];
        } catch (Throwable $error) {
            return (new ErrorEnvelope('rpc.invocation_failed', 'runtime', $error->getMessage()))->jsonSerialize();
        }
    }

    private function arguments(ReflectionMethod $method, array $params): array
    {
        $arguments = [];
        foreach ($method->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (array_key_exists($name, $params)) { $arguments[] = $params[$name]; continue; }
            if ($parameter->isDefaultValueAvailable()) { $arguments[] = $parameter->getDefaultValue(); continue; }
            throw new \InvalidArgumentException("Missing argument: {$name}");
        }
        $unknown = array_diff(array_keys($params), array_map(static fn($p) => $p->getName(), $method->getParameters()));
        if ($unknown !== []) throw new \InvalidArgumentException('Unknown arguments: ' . implode(', ', $unknown));
        return $arguments;
    }
}
