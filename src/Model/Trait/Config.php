<?php

namespace SyncEngine\Model\Trait;

use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\Interface\Persistable;
use SyncEngine\Service\ModelDependencyManager;
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

	/**
	 * Returns the configuration data.
	 *
	 * @param  string|string[]|null  $key
	 * @param  mixed  $default
	 *
	 * @return mixed
	 */
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

	/**
	 * Sets the configuration data.
	 *
	 * @param  mixed  $value
	 * @param  string|string[]|null  $key
	 *
	 * @return void
	 */
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

	public function getConfigDependencies( bool $recurse = false, array $recurseStack = [] ): array
	{
		return $this->getContainer()->get( ModelDependencyManager::class )->getConfigDependencies(
			$this->getConfig(),
			$this->getConfigFields(),
			$recurseStack,
			$recurse
		);
	}

	/**
	 * Returns the field components within the scope of this configuration.
	 *
	 * @return FieldCollection
	 */
	public function getConfigFields(): FieldCollection
	{
		if ( method_exists( $this, 'getFields' ) ) {
			return $this->getFields();
		}

		return new FieldCollection();
	}
}
