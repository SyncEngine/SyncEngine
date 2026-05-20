<?php

namespace SyncEngine\Service;

readonly class ExecuteScheduleResult
{
	public const STATUS_DISPATCHED = 'dispatched';
	public const STATUS_QUEUED     = 'queued';
	public const STATUS_REJECTED   = 'rejected';
	public const STATUS_SKIPPED    = 'skipped';

	private function __construct(
		private readonly string $status,
		private readonly int $traceId = 0,
		private readonly string $reason = '',
	) {}

	public static function dispatched( int $traceId = 0 ): self
	{
		return new self( self::STATUS_DISPATCHED, $traceId );
	}

	public static function queued( int $traceId = 0 ): self
	{
		return new self( self::STATUS_QUEUED, $traceId );
	}

	public static function rejected( string $reason ): self
	{
		return new self( self::STATUS_REJECTED, reason: $reason );
	}

	public static function skipped( string $reason ): self
	{
		return new self( self::STATUS_SKIPPED, reason: $reason );
	}

	public function getStatus(): string
	{
		return $this->status;
	}

	public function getTraceId(): int
	{
		return $this->traceId;
	}

	public function getReason(): string
	{
		return $this->reason;
	}

	public function isDispatched(): bool
	{
		return self::STATUS_DISPATCHED === $this->status;
	}

	public function isQueued(): bool
	{
		return self::STATUS_QUEUED === $this->status;
	}

	public function isRejected(): bool
	{
		return self::STATUS_REJECTED === $this->status;
	}

	public function isSkipped(): bool
	{
		return self::STATUS_SKIPPED === $this->status;
	}
}

