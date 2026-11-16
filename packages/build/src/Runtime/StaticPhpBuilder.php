<?php

declare(strict_types=1);

namespace PhpWails\Build\Runtime;

use RuntimeException;

final class StaticPhpBuilder
{
    public function build(string $spc, string $phar, string $output, string $workdir, array $extensions = [], ?string $phpVersion = null): array
    {
        if (!is_file($spc) || !is_executable($spc)) throw new RuntimeException('static-php-cli executable is missing or not executable.');
        if (!is_file($phar)) throw new RuntimeException("PHAR not found: {$phar}");
        if (!is_dir($workdir) && !mkdir($workdir, 0777, true) && !is_dir($workdir)) throw new RuntimeException("Unable to create runtime work directory: {$workdir}");
        $extensionList = implode(',', array_values(array_unique($extensions ?: ['json', 'phar', 'openssl', 'mbstring', 'curl'])));
        $buildArgs = [$spc, 'build:php', $extensionList, '--build-micro', '--with-micro-fake-cli'];
        if ($phpVersion !== null && $phpVersion !== '') { $buildArgs[] = '--dl-with-php=' . $phpVersion; }
        $this->run($buildArgs, $workdir);
        $micro = $workdir . '/buildroot/bin/micro.sfx';
        if (!is_file($micro)) throw new RuntimeException("static-php-cli did not produce micro.sfx: {$micro}");
        $this->run([$spc, 'micro:combine', $phar, '--output=' . $output, '--with-micro=' . $micro], $workdir);
        if (!is_file($output) || !is_executable($output)) throw new RuntimeException("static runtime was not produced: {$output}");
        return ['type' => 'static-php-micro', 'phpVersion' => $phpVersion, 'extensions' => array_values(array_unique($extensions)), 'digest' => hash_file('sha256', $output)];
    }

    private function run(array $command, string $cwd): void
    {
        $pipes = [];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $cwd);
        if (!is_resource($process)) throw new RuntimeException('Unable to start static-php-cli.');
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit !== 0) throw new RuntimeException(trim($stderr ?: $stdout) ?: 'static-php-cli failed.');
    }
}
