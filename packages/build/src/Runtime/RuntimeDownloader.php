<?php

declare(strict_types=1);

namespace PhpWails\Build\Runtime;

use RuntimeException;

final class RuntimeDownloader
{
    public function __construct(private readonly RuntimeCatalog $catalog, private readonly ?string $cacheRoot = null) {}

    public function resolve(string $platform, string $arch, ?string $override = null, bool $offline = false): array
    {
        $entry = $this->catalog->artifact($platform, $arch);
        if ($override !== null && $override !== '') {
            if (!is_file($override) || !is_executable($override)) throw new RuntimeException('PHPWAILS_SPC must point to an executable file.');
            return ['path' => $override, 'source' => 'override'] + $this->catalog->verifySpc($override, $platform, $arch);
        }
        $cache = $this->cacheRoot ?? $this->defaultCacheRoot();
        $targetDir = rtrim($cache, DIRECTORY_SEPARATOR) . '/tools/spc/' . $entry['sha256'];
        $target = $targetDir . '/spc';
        if (is_file($target)) {
            if (!is_executable($target)) chmod($target, 0755);
            return ['path' => $target, 'source' => 'cache'] + $this->catalog->verifySpc($target, $platform, $arch);
        }
        if ($offline) throw new RuntimeException("Pinned static-php-cli is not cached for {$platform}-{$arch}; offline mode forbids downloading it.");
        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) throw new RuntimeException("Unable to create runtime cache: {$targetDir}");
        $temporary = $targetDir . '/.spc-' . bin2hex(random_bytes(6));
        try {
            $this->download($entry['url'], $temporary);
            if (!hash_equals(strtolower((string) $entry['sha256']), strtolower(hash_file('sha256', $temporary)))) throw new RuntimeException('Downloaded static-php-cli failed SHA-256 verification.');
            chmod($temporary, 0755);
            if (!rename($temporary, $target)) throw new RuntimeException("Unable to publish cached static-php-cli: {$target}");
            return ['path' => $target, 'source' => 'download'] + $this->catalog->verifySpc($target, $platform, $arch);
        } finally {
            if (is_file($temporary)) @unlink($temporary);
        }
    }

    private function download(string $url, string $target): void
    {
        $input = @fopen($url, 'rb');
        if ($input === false) throw new RuntimeException("Unable to download pinned static-php-cli: {$url}");
        $output = @fopen($target, 'wb');
        if ($output === false) { fclose($input); throw new RuntimeException("Unable to write runtime cache: {$target}"); }
        stream_copy_to_stream($input, $output);
        fclose($input); fclose($output);
    }

    private function defaultCacheRoot(): string
    {
        $home = getenv('HOME') ?: sys_get_temp_dir();
        return rtrim($home, DIRECTORY_SEPARATOR) . '/.cache/phpwails';
    }
}
