<?php

namespace SyncEngine\Webservice\Trait;

trait Client
{
	protected static array $clients;
	private $client;

	public function fetchClient( string $ref ): ?object
	{
		return static::$clients[ $ref ] ?? $this->client ?? null;
	}

	protected function cacheClient( mixed $client, string $ref ): void
	{
		static::$clients[ $ref ] = $client;
	}

	abstract public function getClient( array $config );
}
