<?php

declare(strict_types=1);
namespace PhpWails\Runtime\Protocol;

use InvalidArgumentException;

final class JsonRpc
{
    public static function request(string $id, string $method, array $params = []): array
    {
        if ($id === '' || $method === '') throw new InvalidArgumentException('JSON-RPC id and method must be non-empty.');
        return ['jsonrpc'=>'2.0','id'=>$id,'method'=>$method,'params'=>$params];
    }
    public static function notification(string $method, array $params = []): array { return ['jsonrpc'=>'2.0','method'=>$method,'params'=>$params]; }
    public static function response(string $id, mixed $result): array { return ['jsonrpc'=>'2.0','id'=>$id,'result'=>$result]; }
    public static function error(string $id, int $code, string $message, array $data = []): array { return ['jsonrpc'=>'2.0','id'=>$id,'error'=>['code'=>$code,'message'=>$message,'data'=>$data]]; }
    public static function decode(string $json, int $maxDepth = 64): array
    {
        if (strlen($json) > 1024 * 1024) throw new InvalidArgumentException('JSON-RPC frame exceeds 1 MiB.');
        $value = json_decode($json, true, $maxDepth, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        if (!is_array($value) || ($value['jsonrpc'] ?? null) !== '2.0') throw new InvalidArgumentException('Invalid JSON-RPC request.');
        return $value;
    }
}
