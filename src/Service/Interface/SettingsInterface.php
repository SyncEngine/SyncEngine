<?php

namespace SyncEngine\Service\Interface;

interface SettingsInterface
{
	public function get( string $key ): mixed;
	public function set( string $key, mixed $value ): static;
	public function unset( string $key ): static;
	public function fetch(): ?array;
	public function update( string $key, mixed $value ): bool|static;
	public function persist(): bool;
}
