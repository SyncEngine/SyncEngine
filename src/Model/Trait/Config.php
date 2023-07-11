<?php

namespace App\Model\Trait;

trait Config
{
	protected array $config = [];

	public function getConfig( $key = null, $default = null ): mixed
	{
		if ( $key ) {
			return $this->config[ $key ] ?? $default;
		}

		return $this->config;
	}

	public function setConfig( $value, $key = null ): void
	{
		if ( $key ) {
			$this->config[ $key ] = $value;
		} else {
			$this->config = $value;
		}

		if ( isset( $this->entity ) && is_callable( [ $this->entity, 'setConfig' ] ) ) {
			$this->entity->setConfig( $this->config );
		}
	}

	public function getConfigFields(): array
	{
		return [];
	}
}
