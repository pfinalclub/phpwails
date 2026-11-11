<?php
namespace PhpWails\Cli\Init;
use RuntimeException;
final class TemplateCopier {
    public function copy(string $source, string $target, bool $force = false): void {
        if (is_dir($target) && count(array_diff(scandir($target) ?: [], ['.','..'])) > 0 && !$force) throw new RuntimeException('Target directory is not empty.');
        if (!is_dir($target) && !mkdir($target, 0777, true)) throw new RuntimeException('Unable to create target directory.');
        $this->walk($source, $target);
    }
    private function walk(string $source, string $target): void {
        foreach (array_diff(scandir($source) ?: [], ['.','..']) as $name) {
            $from = $source.'/'.$name; $to = $target.'/'.$name;
            if (is_dir($from)) { if (!is_dir($to)) mkdir($to, 0777, true); $this->walk($from, $to); }
            elseif (!copy($from, $to)) throw new RuntimeException("Unable to copy {$name}.");
        }
    }
}
