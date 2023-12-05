<?php

namespace SyncEngine\Model\Trait;

use SyncEngine\Model\Interface\Persistable;
use SyncEngine\Service\ModelNormalizer;
use SyncEngine\Service\ResourceData;

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

		$value = $this->config->get( $key, $default );

		if ( ! $key ) {
			// This isn't part of the model config but for internal use only.
			unset( $value['_dependencies'] );
		}

		return $value;
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
		return $this->getContainer()->get('ModelNormalizer')->getConfigDependencies( $this->getConfig(), $this->getConfigFields(), $recursive );
	}

	public function getConfigFields(): array
	{
		if ( method_exists( $this, 'getFields' ) ) {
			return $this->getFields();
		}
		return [];
	}
}
