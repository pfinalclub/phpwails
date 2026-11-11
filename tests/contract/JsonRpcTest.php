<?php
namespace PhpWails\Tests;
use PHPUnit\Framework\TestCase;
use PhpWails\Runtime\Protocol\JsonRpc;
final class JsonRpcTest extends TestCase { public function testRequestRoundTrip(): void { $request = JsonRpc::request('1', 'App.greet', ['name'=>'World']); self::assertSame('2.0', $request['jsonrpc']); self::assertSame('1', JsonRpc::decode(json_encode($request))['id']); } }
