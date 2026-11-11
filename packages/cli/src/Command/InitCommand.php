<?php
namespace PhpWails\Cli\Command;
use PhpWails\Cli\Init\TemplateCopier;
final class InitCommand { public function run(string $source, string $target, bool $force = false): void { (new TemplateCopier())->copy($source, $target, $force); } }
