<?php

declare(strict_types=1);

namespace PhpWails\Build\Platform;

use RuntimeException;

final class MacOSBundleBuilder
{
    public function writeInfoPlist(string $path, array $app, string $executable, bool $hasIcon = false): void
    {
        $xml = static fn(string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $identifier = $xml((string) ($app['id'] ?? 'com.phpwails.app'));
        $name = $xml((string) ($app['name'] ?? 'PhpWails App'));
        $version = $xml((string) ($app['version'] ?? '0.1.0'));
        $contents = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<!DOCTYPE plist PUBLIC \"-//Apple//DTD PLIST 1.0//EN\" \"http://www.apple.com/DTDs/PropertyList-1.0.dtd\">\n<plist version=\"1.0\"><dict>\n" .
            "<key>CFBundleDevelopmentRegion</key><string>en</string>\n" .
            "<key>CFBundleDisplayName</key><string>{$name}</string>\n" .
            "<key>CFBundleExecutable</key><string>{$xml($executable)}</string>\n" .
            "<key>CFBundleIdentifier</key><string>{$identifier}</string>\n" .
            "<key>CFBundleInfoDictionaryVersion</key><string>6.0</string>\n" .
            "<key>CFBundleName</key><string>{$name}</string>\n" .
            "<key>CFBundlePackageType</key><string>APPL</string>\n" .
            "<key>CFBundleShortVersionString</key><string>{$version}</string>\n" .
            "<key>CFBundleVersion</key><string>{$version}</string>\n" .
            ($hasIcon ? "<key>CFBundleIconFile</key><string>AppIcon.icns</string>\n" : '') .
            "<key>LSMinimumSystemVersion</key><string>13.0</string>\n" .
            "<key>NSHighResolutionCapable</key><true/>\n</dict></plist>\n";
        if (file_put_contents($path, $contents) === false) throw new RuntimeException("Unable to write Info.plist: {$path}");
    }
}
