<?php

declare(strict_types=1);

use PhpWails\Runtime\Attribute\Export;

final class App
{
    #[Export]
    public function greet(string $name = 'World'): string
    {
        return "Hello, {$name}!";
    }

    #[Export]
    public function frameworkInfo(): array
    {
        return [
            'name' => 'PhpWails',
            'php' => PHP_VERSION,
            'protocol' => 'JSON-RPC 2.0 over native WebView messaging',
            'platform' => PHP_OS_FAMILY,
        ];
    }

    #[Export]
    public function validateName(string $name): array
    {
        $name = trim($name);
        return ['valid' => $name !== '', 'message' => $name === '' ? 'Name is required.' : "Input accepted: {$name}"];
    }
}
