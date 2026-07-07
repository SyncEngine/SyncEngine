<?php

namespace SyncEngine\Form\Fields;

use SyncEngine\Form\Fields\Interface\FieldConfigInterface;

/**
 * @psalm-type FieldConfigData array(
 *     name?: string|null
 *     label?: string|null
 *     description?: string|null
 *     type?: string|null
 *     help?: string|array|null
 *     default?: mixed
 *     conditions?: iterable|null
 *     required?: bool|null
 *     disabled?: bool|null
 *     readonly?: bool|null
 * )
 */
class AbstractFieldType extends \ArrayObject implements FieldConfigInterface
{
	/**
	 * @param FieldConfigData $config
	 */
	public function __construct( array $config = [], ...$args )
	{
		parent::__construct( [], ...$args );

		foreach ( $config as $key => $value ) {
			$this->set( $key, $value );
		}
	}

	public function set( string $prop, mixed $value ): static
	{
		static::offsetSet( $prop, $value );

		return $this;
	}

	public function get( string $prop ): mixed
	{
		return static::offsetGet( $prop );
	}

	protected function _set( string $prop, $value ): void
	{
		parent::offsetSet( $prop, $value );
	}

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
		$this->_set( 'name', $name );

		return $this;
	}

	public function getLabel(): string|array
	{
		return $this->_get( 'label', '' );
	}

	public function setLabel( string|array $label ): static
	{
		$this->_set( 'label', $label );

		return $this;
	}

	public function generateLabel( ?string $name = null ): static
	{
		if ( ! $this->getLabel() ) {
			$label = $name ?: $this->getName();
			if ( ! str_starts_with( $label, '_' ) ) {
				$label = ucfirst( str_replace( '_', ' ', $label ) );// . ' (' . $name . ')';
			}
			$this->setLabel( $label );
		}

		return $this;
	}

	public function getType(): string
	{
		return $this->_get( 'type', '' );
	}

	public function setType( string $type ): static
	{
		$this->_set( 'type', $type );

		return $this;
	}

	public function getDescription(): string
	{
		return $this->_get( 'description', '' );
	}

	public function setDescription( string $description ): static
	{
		$this->_set( 'description', $description );

		return $this;
	}

	public function getHelp(): string|array
	{
		return $this->_get( 'help', '' );
	}

	public function setHelp( string|array $help ): static
	{
		$this->_set( 'help', $help );

		return $this;
	}

	public function getDefaultValue(): mixed
	{
		return $this->_get( 'default', null );
	}

	public function setDefaultValue( mixed $defaultValue ): static
	{
		$this->_set( 'default', $defaultValue );

		return $this;
	}

	public function getConditions(): iterable
	{
		return $this->_get( 'conditions', [] );
	}

	public function setConditions( iterable $conditions ): static
	{
		$this->_set( 'conditions', $conditions );

		return $this;
	}

	public function isRequired(): bool
	{
		return $this->_get( 'required', false );
	}

	public function setRequired( bool $required ): static
	{
		$this->_set( 'required', $required );

		return $this;
	}

	public function isDisabled(): bool
	{
		return $this->_get( 'disabled', false );
	}

	public function setDisabled( bool $disabled ): static
	{
		$this->_set( 'disabled', $disabled );

		return $this;
	}

	public function isReadonly(): bool
	{
		return $this->_get( 'readonly', false );
	}

	public function setReadonly( bool $readonly ): static
	{
		$this->_set( 'readonly', $readonly );

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

	public function __clone(): void
	{
		foreach ( $this as $key => $value ) {
			if ( is_object( $value ) ) {
				$this[ $key ] = clone $value;
			}
		}
	}
}
