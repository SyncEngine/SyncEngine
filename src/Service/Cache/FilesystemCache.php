<?php

namespace SyncEngine\Service\Cache;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Service\Attribute\Required;

class FilesystemCache implements CacheInterface
{
	private FilesystemAdapter $cacheAdapter;
	private readonly ?KernelInterface $kernel;

	public function __construct(
		private int $defaultLifetime = 0,
		private ?string $directory = null,
		private ?MarshallerInterface $marshaller = null,
	) {}

	#[Required]
	public function setKernel(KernelInterface $kernel): void
	{
		$this->kernel = $kernel;
	}

	public function setCacheAdapter(
		?int $defaultLifetime = null,
		?string $directory = null,
		?MarshallerInterface $marshaller = null,
	): void
	{
		$this->cacheAdapter = new FilesystemAdapter(
			'syncengine',
			$defaultLifetime ?? $this->defaultLifetime,
			$directory ?: $this->directory,
			$marshaller ?? $this->marshaller
		);
	}

	public function getCacheAdapter() {
		if ( ! isset( $this->cacheAdapter ) ) {
			if ( ! $this->directory ) {
				$this->directory = $this->kernel->getCacheDir();
			}

			$this->setCacheAdapter();
		}

		return $this->cacheAdapter;
	}

	public function get( string $key, callable $callback, ?float $beta = null, ?array &$metadata = null ): mixed
	{
		return $this->getCacheAdapter()->get( $key, $callback, $beta, $metadata );
	}

	public function delete( string $key ): bool
	{
		return $this->getCacheAdapter()->delete( $key );
	}

	public function clear(): bool
	{
		return $this->getCacheAdapter()->clear();
	}
}
