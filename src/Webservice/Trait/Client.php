<?php

namespace SyncEngine\Webservice\Trait;

trait Client
{
	protected static array $clients = [];
	private object $client;

	public function fetchClient( string $ref ): mixed
	{
		return static::$clients[ $ref ] ?? $this->client ?? null;
	}

	abstract public function getClient( array $config = [] ): ?object;

	protected function cacheClient( mixed $client, string $ref ): void
	{
		static::$clients[ $ref ] = $client;
	}

	protected function forgetClient( string $ref ): void
	{
		unset( static::$clients[ $ref ] );
	}
}
