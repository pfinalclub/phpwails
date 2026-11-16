<?php

declare(strict_types=1);

namespace PhpWails\Build\Manifest;

final class BuildManifest
{
    public static function create(string $buildId, array $target, string $output, int $size, string $digest, string $resourceDigest): array
    {
        return ['schemaVersion' => 1, 'buildId' => $buildId, 'target' => $target, 'output' => basename($output), 'sizeBytes' => $size, 'artifactDigest' => $digest, 'resourceDigest' => $resourceDigest, 'createdAt' => gmdate('c')];
    }
}
