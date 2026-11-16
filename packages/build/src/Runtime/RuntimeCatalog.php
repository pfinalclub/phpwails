<?php

declare(strict_types=1);

namespace PhpWails\Build\Runtime;

use RuntimeException;

final class RuntimeCatalog
{
    public function __construct(private readonly array $catalog) {}

    public static function fromFile(string $path): self
    {
        if (!is_file($path)) throw new RuntimeException("Runtime catalog not found: {$path}");
        $data = json_decode((string) file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($data) || !isset($data['artifacts']) || !is_array($data['artifacts'])) throw new RuntimeException('Invalid runtime catalog.');
        return new self($data);
    }

    public function artifact(string $platform, string $arch): array
    {
        $key = $platform . '-' . $arch;
        $entry = $this->catalog['artifacts'][$key] ?? null;
        if (!is_array($entry) || !isset($entry['sha256'], $entry['url'])) throw new RuntimeException("No pinned static-php-cli artifact for {$key}.");
        return $entry + ['key' => $key, 'channel' => $this->catalog['toolChannel'] ?? 'unknown', 'provider' => $this->catalog['provider'] ?? 'unknown'];
    }

    public function verifySpc(string $path, string $platform, string $arch): array
    {
        $entry = $this->artifact($platform, $arch);
        $actual = hash_file('sha256', $path);
        if (!hash_equals(strtolower((string) $entry['sha256']), strtolower($actual))) throw new RuntimeException("static-php-cli digest mismatch for {$platform}-{$arch}: expected {$entry['sha256']}, got {$actual}");
        return ['provider' => $entry['provider'], 'channel' => $entry['channel'], 'sha256' => $actual, 'url' => $entry['url']];
    }
}
