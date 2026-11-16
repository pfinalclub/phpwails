<?php

declare(strict_types=1);

if ($argc < 4) { fwrite(STDERR, "Usage: create-phar.php <project> <framework> <output>\n"); exit(2); }
$project = realpath($argv[1]);
$framework = realpath($argv[2]);
$output = $argv[3];
if ($project === false || $framework === false) throw new RuntimeException('Project or framework root is not readable.');
@unlink($output);
$phar = new Phar($output, 0, 'app.phar');
$phar->startBuffering();
$phar->buildFromDirectory($project, '/^(?!.*(?:[\\\\\/]build|[\\\\\/]\.git)(?:[\\\\\/]|$)).*/');
$runtime = $framework . '/packages/runtime';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($runtime, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    $relative = substr($file->getPathname(), strlen($framework) + 1);
    $phar->addFile($file->getPathname(), $relative);
}
$phar->addFromString('bootstrap.php', <<<'BOOTSTRAP'
<?php
require 'phar://app.phar/packages/runtime/bin/phpwails-runtime.php';
BOOTSTRAP
);
$phar->setStub(<<<'STUB'
<?php
Phar::mapPhar('app.phar');
require 'phar://app.phar/bootstrap.php';
__HALT_COMPILER();
STUB
);
$phar->setSignatureAlgorithm(Phar::SHA256);
$phar->stopBuffering();
