<?php

namespace App\Model\Trait;

use App\Model\Interface\Persistable;
use App\Service\ModelNormalizer;

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

		if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'setConfig' ] ) ) {
			$this->getEntity()->setConfig( $this->config );
		}
	}

	public function fetchConfigDependencies(): void
	{
		$store = array_keys( $this->getConfigDependencies() );
		$this->setConfig( $store, '_dependencies' );
	}

	public function getConfigDependencies( $append = [] ): array
	{
		return ( new ModelNormalizer() )->parseConfigDependencies( $this->getConfig(), $this->getConfigFields(), $append );
	}

	public function getConfigFields(): array
	{
		if ( method_exists( $this, 'getFields' ) ) {
			return $this->getFields();
		}
		return [];
	}
}
