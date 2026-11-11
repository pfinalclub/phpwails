<?php

namespace PhpWails\Cli\Dev;

use PhpWails\Runtime\Config\ProjectConfig;

final class DevSupervisor
{
    public function validate(string $projectRoot): array
    {
        $config = ProjectConfig::fromFile($projectRoot . '/phpwails.json');
        return ['projectRoot' => $projectRoot, 'app' => $config->app['name'] ?? 'Unnamed', 'status' => 'ready', 'nativeHost' => 'pending'];
    }
}
