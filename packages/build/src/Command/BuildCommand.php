<?php

declare(strict_types=1);

namespace PhpWails\Build\Command;

use PhpWails\Build\Build\BuildPipeline;

final class BuildCommand
{
    public function __construct(private readonly string $frameworkRoot) {}
    public function run(string $project, string $platform = 'darwin', ?string $arch = null, string $format = 'app', bool $selfContained = false, bool $offline = false): array
    {
        return (new BuildPipeline($this->frameworkRoot))->build($project, $platform, $arch, $format, $selfContained, $offline);
    }
}
