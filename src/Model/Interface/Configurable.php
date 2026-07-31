<?php

namespace SyncEngine\Model\Interface;

use SyncEngine\Form\Fields\Collection\FieldCollection;

interface Configurable
{
	/**
	 * Returns the fields available for this entity.
	 *
	 * @return FieldCollection
	 */
	public function getFields(): FieldCollection;

	/**
	 * Returns the configuration data.
	 *
	 * @param  string|string[]|null  $key
	 * @param  mixed  $default
	 *
	 * @return mixed
	 */
	public function getConfig( $key = null, $default = null ): mixed;

	/**
	 * Sets the configuration data.
	 *
	 * @param  mixed  $value
	 * @param  string|string[]|null  $key
	 *
	 * @return void
	 */
	public function setConfig( $value, $key = null ): void;

	/**
	 * @param  iterable  $config
	 *
	 * @return void
	 */
	public function updateConfig( iterable $config ): void;
}
