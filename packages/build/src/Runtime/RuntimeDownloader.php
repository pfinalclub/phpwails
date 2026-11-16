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
            try {
                if (!is_executable($target)) chmod($target, 0755);
                return ['path' => $target, 'source' => 'cache'] + $this->catalog->verifySpc($target, $platform, $arch);
            } catch (RuntimeException $error) {
                @unlink($target);
                if ($offline) throw $error;
            }
        }
        if ($offline) throw new RuntimeException("Pinned static-php-cli is not cached for {$platform}-{$arch}; offline mode forbids downloading it.");
        if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) throw new RuntimeException("Unable to create runtime cache: {$targetDir}");
        $temporary = $targetDir . '/.spc-' . bin2hex(random_bytes(6));
        try {
            $expected = strtolower((string) $entry['sha256']);
            $actual = '';
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $this->download($entry['url'], $temporary);
                $actual = strtolower(hash_file('sha256', $temporary));
                if (hash_equals($expected, $actual)) break;
                @unlink($temporary);
                if ($attempt < 3) usleep(250000);
            }
            if (!hash_equals($expected, $actual)) throw new RuntimeException("Downloaded static-php-cli failed SHA-256 verification for {$entry['key']}: expected {$expected}, got {$actual}");
            chmod($temporary, 0755);
            if (!rename($temporary, $target)) throw new RuntimeException("Unable to publish cached static-php-cli: {$target}");
            return ['path' => $target, 'source' => 'download'] + $this->catalog->verifySpc($target, $platform, $arch);
        } finally {
            if (is_file($temporary)) @unlink($temporary);
        }
    }

    private function download(string $url, string $target): void
    {
        if (function_exists('curl_init')) {
            $handle = curl_init($url);
            $output = @fopen($target, 'wb');
            if ($handle === false || $output === false) { if (is_resource($output)) fclose($output); throw new RuntimeException("Unable to download pinned static-php-cli: {$url}"); }
            curl_setopt_array($handle, [CURLOPT_FILE => $output, CURLOPT_FOLLOWLOCATION => true, CURLOPT_FAILONERROR => true, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 600, CURLOPT_USERAGENT => 'PhpWails Build Runtime']);
            $ok = curl_exec($handle);
            $error = curl_error($handle);
            fclose($output);
            if ($ok === false) { @unlink($target); throw new RuntimeException("Unable to download pinned static-php-cli: {$error}"); }
            return;
        }
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
