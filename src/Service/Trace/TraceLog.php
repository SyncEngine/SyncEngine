<?php

namespace SyncEngine\Service\Trace;

use SyncEngine\Service\ResourceData;
use SyncEngine\Service\Trace\Enum\TraceLogType;

class TraceLog
{
	protected string $message = '';
	protected int|float $timestamp;
	protected TraceLogType $type;
	protected ?ResourceData $info = null;
	protected ?TraceContext $traceContext = null;

	public function __construct( string|array|ResourceData $message, string|TraceLogType $type = 'log', TraceContext $traceContext = null )
	{
		if ( is_array( $message ) ) {
			$this->message = $message['message'] ?? '';
			$this->info = ResourceData::create( $message );
		} else {
			$this->message = $message;
		}

		$this->setTimestamp();
		$this->type = TraceLogType::create( $type );
		$this->traceContext = $traceContext;
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	public function setTimestamp( float $microtime = null ): static
	{
		$this->timestamp = $microtime ?: microtime( true );
	}

	public function getTimestamp( $microtime = true ): int|float
	{
		return $microtime ? $this->timestamp : (int) $this->timestamp;
	}

	public function getContext(): ?TraceContext
	{
		return $this->traceContext;
	}

	public function getType(): TraceLogType
	{
		return $this->type;
	}

	public function getTypeString(): string
	{
		return $this->type->value;
	}
}
