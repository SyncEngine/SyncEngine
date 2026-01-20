<?php

namespace SyncEngine\Model\Trait;

use SyncEngine\Model\Interface\Persistable;
use SyncEngine\Service\ModelNormalizer;
use SyncEngine\Structure\Data\ConfigData;

trait Config
{
	protected ConfigData $config;

	protected function initConfig(): void
	{
		$this->config = new ConfigData();

		if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'getConfig' ] ) ) {
			$this->config->set( (array) $this->getEntity()->getConfig() );
		}
	}

	public function getConfig( $key = null, $default = null ): mixed
	{
		if ( ! isset( $this->config ) ) {
			$this->initConfig();
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
			$this->initConfig();
		}

		$this->config->set( $value, $key );

		$this->updateConfig( $this->config->sanitize( $this->getConfigFields() ) );
	}

	public function exportConfig(): array
	{
		if ( $this instanceof Persistable ) {
			$config = $this->getEntity()->getConfig();
		} else {
			$config = $this->getConfig();
		}

		// Depencies are internal only.
		unset( $config['_dependencies'] );

		return $config;
	}

	public function updateConfig( $config ): void
	{
		if ( $this instanceof Persistable && is_callable( [ $this->getEntity(), 'setConfig' ] ) ) {
			$this->getEntity()->setConfig( $config );
		}
	}

	public function fetchConfigDependencies(): void
	{
		$this->setConfig( array_keys( $this->getConfigDependencies() ), '_dependencies' );
	}

	public function getConfigDependencies( array|bool $recursive = false ): array
	{
		return $this->getContainer()->get( ModelNormalizer::class )->getConfigDependencies(
			$this->getConfig(),
			$this->getConfigFields(),
			$recursive
		);
	}

	public function getConfigFields(): array
	{
		if ( method_exists( $this, 'getFields' ) ) {
			return $this->getFields();
		}

		return [];
	}
}
