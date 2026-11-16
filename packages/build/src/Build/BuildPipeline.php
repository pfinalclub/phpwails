<?php

declare(strict_types=1);

namespace PhpWails\Build\Build;

use PhpWails\Build\Manifest\BuildManifest;
use PhpWails\Build\Platform\MacOSBundleBuilder;
use PhpWails\Build\Resources\ResourceCollector;
use PhpWails\Codegen\BindingCompiler;
use PhpWails\Runtime\Config\ProjectConfig;
use RuntimeException;

final class BuildPipeline
{
    public function __construct(private readonly string $frameworkRoot) {}

    public function build(string $projectRoot, string $platform = 'darwin', ?string $arch = null, string $format = 'app'): array
    {
        $projectRoot = realpath($projectRoot) ?: $projectRoot;
        if ($platform !== 'darwin' || $format !== 'app') throw new RuntimeException('The first packaging target is macOS .app on the current host.');
        if (!is_dir($projectRoot)) throw new RuntimeException("Project directory not found: {$projectRoot}");
        $config = ProjectConfig::fromFile($projectRoot . '/phpwails.json');
        $arch ??= php_uname('m');
        if (!in_array($arch, ['arm64', 'x86_64'], true)) throw new RuntimeException("Unsupported macOS architecture: {$arch}");
        if ($arch !== php_uname('m')) throw new RuntimeException("Cross-building macOS {$arch} is not enabled on this host.");

        $launcher = $this->frameworkRoot . '/build/native/phpwails-native';
        if (!is_file($launcher)) throw new RuntimeException("Native launcher not found: {$launcher}. Build it with CMake first.");
        $appFile = $projectRoot . '/App.php';
        if (!is_file($appFile)) throw new RuntimeException("App.php not found in {$projectRoot}.");
        $entryRelative = (string) ($config->frontend['entryDocument'] ?? 'frontend/index.html');
        $entry = $this->inside($projectRoot, $projectRoot . '/' . ltrim($entryRelative, '/'));
        if (!is_file($entry)) throw new RuntimeException("Frontend entry not found: {$entry}");

        require_once $this->frameworkRoot . '/packages/runtime/src/Attribute/Export.php';
        require_once $this->frameworkRoot . '/packages/codegen/src/BindingCompiler.php';
        require_once $appFile;
        if (!class_exists('App')) throw new RuntimeException('App class not found.');
        $compiler = new BindingCompiler();
        $compiler->write($compiler->compile(new \App(), 'App'), $projectRoot . '/frontend/phpwails');

        $appName = $this->safeName((string) ($config->app['name'] ?? 'PhpWailsApp'));
        $outputDir = $this->inside($projectRoot, $projectRoot . '/' . ($config->build['outputDir'] ?? 'build'));
        if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) throw new RuntimeException("Unable to create output directory: {$outputDir}");
        $staging = $outputDir . '/.staging-' . bin2hex(random_bytes(6));
        $bundle = $staging . '/' . $appName . '.app';
        $contents = $bundle . '/Contents';
        $resources = $contents . '/Resources';
        $collector = new ResourceCollector();
        try {
            mkdir($contents . '/MacOS', 0777, true);
            mkdir($resources, 0777, true);
            $collector->copyFile($launcher, $contents . '/MacOS/' . $appName);
            chmod($contents . '/MacOS/' . $appName, 0755);
            $collector->copyTree($this->frameworkRoot . '/packages/runtime', $resources . '/packages/runtime');
            $collector->copyTree($projectRoot, $resources . '/project', ['build', '.git']);
            $frontendSource = dirname($entry);
            $collector->copyTree($frontendSource, $resources . '/frontend');
            $icon = $config->app['icon'] ?? null;
            if (is_string($icon) && $icon !== '') $collector->copyFile($this->inside($projectRoot, $projectRoot . '/' . ltrim($icon, '/')), $resources . '/AppIcon.icns');
            $launchConfig = [
                'title' => $config->window['title'] ?? $appName,
                'width' => (int) ($config->window['width'] ?? 1024),
                'height' => (int) ($config->window['height'] ?? 768),
                'centered' => (bool) ($config->window['centered'] ?? true),
                'app' => $config->app,
                'entry' => 'frontend/' . basename($entry),
                'project' => 'project',
                'runtimeRoot' => 'packages/runtime',
            ];
            file_put_contents($resources . '/launch.json', json_encode($launchConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
            (new MacOSBundleBuilder())->writeInfoPlist($contents . '/Info.plist', $config->app, $appName, is_string($icon) && $icon !== '');
            $resourceDigest = $collector->digest($resources);
            $artifactDigest = hash_file('sha256', $contents . '/MacOS/' . $appName);
            $buildManifest = BuildManifest::create(bin2hex(random_bytes(12)), ['platform' => $platform, 'arch' => $arch, 'format' => $format], $bundle, (int) filesize($contents . '/MacOS/' . $appName), $artifactDigest, $resourceDigest);
            file_put_contents($resources . '/build-manifest.json', json_encode($buildManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
            $destination = $outputDir . '/' . $appName . '.app';
            if (is_dir($destination)) $this->removeTree($destination);
            if (!rename($bundle, $destination)) throw new RuntimeException("Unable to publish {$destination}");
            return ['output' => $destination, 'app' => $appName, 'target' => ['platform' => $platform, 'arch' => $arch, 'format' => $format], 'manifest' => $buildManifest];
        } finally {
            if (is_dir($staging)) $this->removeTree($staging);
        }
    }

    private function inside(string $root, string $path): string
    {
        $root = rtrim(realpath($root) ?: $root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $normalized = str_replace('\\', '/', $path);
        $rootNormalized = rtrim(str_replace('\\', '/', $root), '/') . '/';
        if (!str_starts_with($normalized, $rootNormalized)) throw new RuntimeException("Path escapes project root: {$path}");
        return rtrim($path, DIRECTORY_SEPARATOR);
    }
    private function safeName(string $name): string { $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($name)) ?: 'PhpWailsApp'; return trim($name, '.-') ?: 'PhpWailsApp'; }
    private function removeTree(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $name) { $item = $path . '/' . $name; is_dir($item) ? $this->removeTree($item) : unlink($item); } rmdir($path); }
}
