<?php

namespace SyncEngine\Form\Fields;

use SyncEngine\Form\Fields\Interface\FieldConfigInterface;

class AbstractFieldType extends \ArrayObject implements FieldConfigInterface
{
	protected function _get( string $prop, $default = null )
	{
		return parent::offsetExists( $prop ) ? parent::offsetGet( $prop ) : $default;
	}

	public function getName(): string
	{
		return $this->_get( 'name', '' );
	}

	public function setName( string $name ): static
	{
		parent::offsetSet( 'name', $name );

		return $this;
	}

	public function getLabel(): string|array
	{
		return $this->_get( 'label', '' );
	}

	public function setLabel( string|array $label ): static
	{
		parent::offsetSet( 'label', $label );

		return $this;
	}

	public function getType(): string
	{
		return $this->_get( 'type', '' );
	}

	public function setType( string $type ): static
	{
		parent::offsetSet( 'type', $type );

		return $this;
	}

	public function getDescription(): string
	{
		return $this->_get( 'description', '' );
	}

	public function setDescription( string $description ): static
	{
		parent::offsetSet( 'description', $description );

		return $this;
	}

	public function getHelp(): string|array
	{
		return $this->_get( 'help', '' );
	}

	public function setHelp( string|array $help ): static
	{
		parent::offsetSet( 'help', $help );

		return $this;
	}

	public function getDefaultValue(): mixed
	{
		return $this->_get( 'default', null );
	}

	public function setDefaultValue( mixed $defaultValue ): static
	{
		parent::offsetSet( 'default', $defaultValue );

		return $this;
	}

	public function getConditions(): iterable
	{
		return $this->_get( 'conditions', [] );
	}

	public function setConditions( iterable $conditions ): static
	{
		parent::offsetSet( 'conditions', $conditions );

		return $this;
	}

	public function isRequired(): bool
	{
		return $this->_get( 'required', false );
	}

	public function setRequired( bool $required ): static
	{
		parent::offsetSet( 'required', $required );

		return $this;
	}

	public function isDisabled(): bool
	{
		return $this->_get( 'disabled', false );
	}

	public function setDisabled( bool $disabled ): static
	{
		parent::offsetSet( 'disabled', $disabled );

		return $this;
	}

	public function isReadonly(): bool
	{
		return $this->_get( 'readonly', false );
	}

	public function setReadonly( bool $readonly ): static
	{
		parent::offsetSet( 'readonly', $readonly );

		return $this;
	}

	public function offsetGet( mixed $key ): mixed
	{
		$method = 'get' . ucfirst( $key );
		if ( method_exists( $this, $method ) ) {
			return $this->$method();
		}
		return parent::offsetGet( $key );
	}

	public function offsetSet( mixed $key, mixed $value ): void
	{
		$method = 'set' . ucfirst( $key );
		if ( method_exists( $this, $method ) ) {
			$this->$method( $value );
		}
		parent::offsetSet( $key, $value );
	}
}
