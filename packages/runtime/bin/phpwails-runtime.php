#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
require_once $root . '/packages/runtime/src/Attribute/Export.php';
require_once $root . '/packages/runtime/src/Error/ErrorEnvelope.php';
require_once $root . '/packages/runtime/src/Dispatch/ExportDispatcher.php';

$project = $argv[1] ?? getcwd();
$appFile = rtrim($project, DIRECTORY_SEPARATOR) . '/App.php';
if (!is_file($appFile)) { fwrite(STDERR, "App.php not found in {$project}\n"); exit(2); }
require_once $appFile;
if (!class_exists('App')) { fwrite(STDERR, "App class not found\n"); exit(2); }
$dispatcher = new PhpWails\Runtime\Dispatch\ExportDispatcher();
$dispatcher->register(new App(), 'App');

$write = static function (array $response): void {
    fwrite(STDOUT, json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    fflush(STDOUT);
};

while (($line = fgets(STDIN)) !== false) {
    $line = trim($line);
    if ($line === '') continue;
    if (strlen($line) > 1024 * 1024) {
        $write(['jsonrpc' => '2.0', 'id' => null, 'error' => ['code' => -32600, 'message' => 'Request exceeds 1 MiB.']]);
        continue;
    }

    $id = null;
    $hasId = false;
    try {
        $request = json_decode($line, true, 64, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        if (!is_array($request) || ($request['jsonrpc'] ?? null) !== '2.0') {
            throw new InvalidArgumentException('Invalid JSON-RPC request.');
        }
        $hasId = array_key_exists('id', $request);
        $id = $request['id'] ?? null;
        if ($hasId && !is_string($id) && !is_int($id)) {
            throw new InvalidArgumentException('JSON-RPC id must be a string or integer.');
        }
        $method = $request['method'] ?? null;
        if (!is_string($method) || $method === '') throw new InvalidArgumentException('JSON-RPC method must be a non-empty string.');
        $params = $request['params'] ?? [];
        if (!is_array($params)) throw new InvalidArgumentException('JSON-RPC params must be an object or array.');

        $result = $dispatcher->invoke($method, $params);
        if (!$hasId) continue; // Notification: execute without a response.
        if (isset($result['ok']) && $result['ok'] === true) {
            $write(['jsonrpc' => '2.0', 'id' => $id, 'result' => $result['result']]);
        } else {
            $write(['jsonrpc' => '2.0', 'id' => $id, 'error' => [
                'code' => -32001,
                'message' => $result['message'] ?? 'Invocation failed',
                'data' => $result,
            ]]);
        }
    } catch (Throwable $error) {
        $write(['jsonrpc' => '2.0', 'id' => $hasId ? $id : null, 'error' => [
            'code' => -32600,
            'message' => $error->getMessage(),
        ]]);
    }
}
