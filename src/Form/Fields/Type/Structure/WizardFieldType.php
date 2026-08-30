<?php

namespace SyncEngine\Form\Fields\Type\Structure;

use SyncEngine\Form\Fields\AbstractFieldType;
use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;

class WizardFieldType extends AbstractFieldType
{
	public function setSteps( FieldCollection|array $steps ): static
	{
		if ( ! $steps instanceof FieldCollection ) {
			$steps = new FieldCollection( $steps );
		}

		$this->_set( 'steps', $steps );

		return $this;
	}

	public function getSteps(): FieldCollection
	{
		if ( ! isset( $this['steps'] ) ) {
			$this->_set( 'steps', new FieldCollection() );
		}

		return $this->_get( 'steps' );
	}

	public function getStep( string $name ): ?FieldConfigInterface
	{
		if ( isset( $this['steps'] ) ) {
			return $this['steps'][ $name ];
		}

		return null;
	}

	public function addStep( string $name, FieldConfigInterface|array $config ): static
	{
		if ( empty( $this['steps'] ) ) {
			$this['steps'] = new FieldCollection();
		}

		$this['steps'][ $name ] = $config;

		return $this;
	}

	public function removeStep( string $name ): static
	{
		if ( isset( $this['steps'] ) ) {
			unset( $this['steps'][ $name ] );
		}

		return $this;
	}
}
