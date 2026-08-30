<?php

namespace SyncEngine\Form\Fields\Type\Structure;

use SyncEngine\Form\Fields\AbstractFieldType;
use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;

class TabsFieldType extends AbstractFieldType
{
	public function setTabs( FieldCollection|array $tabs ): static
	{
		if ( ! $tabs instanceof FieldCollection ) {
			$tabs = new FieldCollection( $tabs );
		}

		$this->_set( 'tabs', $tabs );

		return $this;
	}

	public function getTabs(): FieldCollection
	{
		if ( ! isset( $this['tabs'] ) ) {
			$this->_set( 'tabs', new FieldCollection() );
		}

		return $this->_get( 'tabs' );
	}

	public function getTab( string $name ): ?FieldConfigInterface
	{
		if ( isset( $this['tabs'] ) ) {
			return $this['tabs'][ $name ];
		}

		return null;
	}

	public function addTab( string $name, FieldConfigInterface|array $config ): static
	{
		if ( empty( $this['tabs'] ) ) {
			$this['tabs'] = new FieldCollection();
		}

		$this['tabs'][ $name ] = $config;

		return $this;
	}

	public function removeTab( string $name ): static
	{
		if ( isset( $this['tabs'] ) ) {
			unset( $this['tabs'][ $name ] );
		}

		return $this;
	}
}
