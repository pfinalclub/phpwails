<?php

declare(strict_types=1);

namespace PhpWails\Build\Archive;

use RuntimeException;

final class PharBuilder
{
    public function __construct(private readonly string $frameworkRoot) {}

    public function build(string $projectRoot, string $output): void
    {
        $helper = $this->frameworkRoot . '/packages/build/bin/create-phar.php';
        if (!is_file($helper)) throw new RuntimeException('PHAR builder helper is missing.');
        $command = [PHP_BINARY, '-d', 'phar.readonly=0', $helper, $projectRoot, $this->frameworkRoot, $output];
        $pipes = [];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) throw new RuntimeException('Unable to start PHAR builder.');
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit !== 0 || !is_file($output)) throw new RuntimeException(trim($stderr ?: $stdout) ?: 'PHAR build failed.');
    }
}
