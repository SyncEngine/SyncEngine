<?php

namespace App\Model\Trait;

use App\Model\Interface\Persistable;
use App\Service\ModelNormalizer;
use App\Service\ResourceData;

trait Config
{
	protected ResourceData $config;

	public function getConfig( $key = null, $default = null ): mixed
	{
		if ( ! isset( $this->config ) ) {
			$this->config = new ResourceData();
			if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'getConfig' ] ) ) {
				$this->config->set( (array) $this->getEntity()->getConfig() );
			}
		}

		return $this->config->get( $key, $default );
	}

	public function setConfig( $value, $key = null ): void
	{
		if ( ! isset( $this->config ) ) {
			$this->getConfig();
		}

		$this->config->set( $value, $key );

		if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'setConfig' ] ) ) {
			$this->getEntity()->setConfig( $this->config->getArrayCopy() );
		}
	}

	public function fetchConfigDependencies(): void
	{
		$this->setConfig( array_keys( $this->getConfigDependencies() ), '_dependencies' );
	}

	public function getConfigDependencies( array|bool $recursive = false ): array
	{
		return ( new ModelNormalizer() )->parseConfigDependencies( $this->getConfig(), $this->getConfigFields(), $recursive );
	}

	public function getConfigFields(): array
	{
		if ( method_exists( $this, 'getFields' ) ) {
			return $this->getFields();
		}
		return [];
	}
}
