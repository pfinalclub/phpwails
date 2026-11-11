<?php

namespace PhpWails\Cli\Command;

use PhpWails\Cli\Dev\DevSupervisor;

final class DevCommand
{
    public function run(string $projectRoot): array
    {
        return (new DevSupervisor())->validate($projectRoot);
    }
}
