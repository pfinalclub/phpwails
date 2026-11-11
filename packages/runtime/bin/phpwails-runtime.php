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

while (($line = fgets(STDIN)) !== false) {
    $line = trim($line);
    if ($line === '') continue;
    try {
        $request = json_decode($line, true, 64, JSON_THROW_ON_ERROR);
        if (is_string($request['params'] ?? null)) $request = json_decode($request['params'], true, 64, JSON_THROW_ON_ERROR);
        $id = is_string($request['id'] ?? null) ? $request['id'] : null;
        $method = is_string($request['method'] ?? null) ? $request['method'] : null;
        if ($id === null || $method === null || !is_array($request['params'] ?? [])) throw new InvalidArgumentException('Invalid JSON-RPC request.');
        $result = $dispatcher->invoke($method, $request['params']);
        $response = isset($result['ok']) ? ['jsonrpc'=>'2.0','id'=>$id,'result'=>$result['result']] : ['jsonrpc'=>'2.0','id'=>$id,'error'=>['code'=>-32001,'message'=>$result['message'] ?? 'Invocation failed','data'=>$result]];
    } catch (Throwable $error) {
        $response = ['jsonrpc'=>'2.0','id'=>$id ?? null,'error'=>['code'=>-32600,'message'=>$error->getMessage()]];
    }
    fwrite(STDOUT, json_encode($response, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . PHP_EOL);
    fflush(STDOUT);
}
