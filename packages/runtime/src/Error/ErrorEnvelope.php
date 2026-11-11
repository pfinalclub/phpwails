<?php

declare(strict_types=1);
namespace PhpWails\Runtime\Error;

final readonly class ErrorEnvelope implements \JsonSerializable
{
    public function __construct(public string $code, public string $category, public string $message, public ?string $correlationId = null, public bool $retryable = false, public array $details = []) {}
    public function jsonSerialize(): array { return array_filter(['code'=>$this->code,'category'=>$this->category,'message'=>$this->message,'correlationId'=>$this->correlationId,'retryable'=>$this->retryable,'details'=>$this->details], static fn($v) => $v !== null); }
}
