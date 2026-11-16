<?php

declare(strict_types=1);

namespace PhpWails\Build\Resources;

use RuntimeException;

final class ResourceCollector
{
    public function copyTree(string $source, string $target, array $exclude = []): void
    {
        if (!is_dir($source)) throw new RuntimeException("Resource directory not found: {$source}");
        if (!is_dir($target) && !mkdir($target, 0777, true) && !is_dir($target)) throw new RuntimeException("Unable to create resource directory: {$target}");
        foreach (array_diff(scandir($source) ?: [], ['.', '..']) as $name) {
            if (in_array($name, $exclude, true)) continue;
            $from = $source . DIRECTORY_SEPARATOR . $name;
            $to = $target . DIRECTORY_SEPARATOR . $name;
            if (is_dir($from)) $this->copyTree($from, $to, $exclude);
            elseif (!copy($from, $to)) throw new RuntimeException("Unable to copy resource: {$from}");
        }
    }

    public function copyFile(string $source, string $target): void
    {
        if (!is_file($source)) throw new RuntimeException("Resource file not found: {$source}");
        $parent = dirname($target);
        if (!is_dir($parent) && !mkdir($parent, 0777, true) && !is_dir($parent)) throw new RuntimeException("Unable to create directory: {$parent}");
        if (!copy($source, $target)) throw new RuntimeException("Unable to copy resource: {$source}");
    }

    public function digest(string $directory): string
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) if ($file->isFile()) $files[] = str_replace('\\', '/', substr($file->getPathname(), strlen($directory) + 1));
        sort($files);
        $hash = hash_init('sha256');
        foreach ($files as $relative) { hash_update($hash, $relative . "\0"); hash_update($hash, hash_file('sha256', $directory . DIRECTORY_SEPARATOR . $relative)); hash_update($hash, "\0"); }
        return hash_final($hash);
    }
}
