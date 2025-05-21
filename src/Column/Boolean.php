<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\BooleanColumnType;
use SyncEngine\Form\Fields\InputFieldType;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Format\BooleanFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class Boolean extends ColumnModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = BooleanColumnType::TYPE;
		$this->name        = $this->trans( 'Boolean' );
		$this->description = $this->trans( 'Format as true or false' );
	}

	public function getFields(): array
	{
		return [
			'format'       => [
				'label'   => $this->trans( 'Format' ),
				'type'    => 'select',
				'choices' => [
					'bool'    => $this->trans( 'Boolean' ) . ' (true/false)',
					'int'     => $this->trans( 'Integer' ) . ' (1/0)',
					'string'  => $this->trans( 'Custom text' ),
					'numeric' => $this->trans( 'Custom number' ),
				],
			],
			'true_value'   => [
				'label'      => $this->trans( 'True value' ),
				'type'       => 'text',
				'conditions' => [ 'format' => [ 'string', 'numeric' ] ],
			],
			'false_value'  => [
				'label'      => $this->trans( 'False value' ),
				'type'       => 'text',
				'conditions' => [ 'format' => [ 'string', 'numeric' ] ],
			],
			'fallback_value' => [
				'label' => $this->trans( 'Fallback value' ),
				'type'    => 'select',
				'choices' => [
					''       => $this->trans( 'Null / Empty' ),
					'true'   => $this->trans( 'True value' ),
					'false'  => $this->trans( 'False value' ),
					'custom' => $this->trans( 'Custom' ),
				],
			],
			'fallback_custom' => [
				'label' => $this->trans( 'Fallback custom value' ),
				'type'    => 'text',
			],
		];
	}

	public function getInput( array $config = [] ): ?FieldConfigInterface
	{
		$config = $this->parseConfig( $config );

		$choices = [];
		$choices[ $config[ BooleanFormatter::TRUE_VALUE ] ] = $this->toLabel( $config[ BooleanFormatter::TRUE_VALUE ], $config );
		$choices[ $config[ BooleanFormatter::FALSE_VALUE ] ] = $this->toLabel( $config[ BooleanFormatter::FALSE_VALUE ], $config );

		$field = [
			'type' => 'select',
			'choices' => $choices,
			'customizable' => true,
		];

		return new InputFieldType( $field );
	}

	public function initFormatter( $config = [] ): FormatInterface
	{
		return new BooleanFormatter( $this->parseConfig( $config ) );
	}

	protected function parseConfig( $config ): array
	{
		$context[ BooleanFormatter::FORMAT ] = $config['format'] ?? 'bool';

		switch ( $config['fallback_value'] ?? '' ) {
			case 'true':
				$context[ BooleanFormatter::NULL_VALUE ] = $config['true_value'] ?? null;
			break;
			case 'false':
				$context[ BooleanFormatter::NULL_VALUE ] = $config['false_value'] ?? null;
			break;
			case 'custom':
				$context[ BooleanFormatter::NULL_VALUE ] = $config['fallback_custom'] ?? null;
			break;
		}

		switch ( $context[ BooleanFormatter::FORMAT ] ) {
			case 'string':
				$context[ BooleanFormatter::TRUE_VALUE ] = $config['true_value'] ?? '';
				$context[ BooleanFormatter::FALSE_VALUE ] = $config['false_value'] ?? '';
			break;
			case 'numeric':
				$context[ BooleanFormatter::TRUE_VALUE ] = $this->toNumeric( $config['true_value'] ?? '' );
				$context[ BooleanFormatter::FALSE_VALUE ] = $this->toNumeric( $config['false_value'] ?? '' );
				if ( isset( $context[ BooleanFormatter::NULL_VALUE ] ) ) {
					$context[ BooleanFormatter::NULL_VALUE ] = $this->toNumeric( $context[ BooleanFormatter::NULL_VALUE ] );
				}
			break;
			case 'int':
				$context[ BooleanFormatter::TRUE_VALUE ] = 1;
				$context[ BooleanFormatter::FALSE_VALUE ] = 0;
			break;
			default:
				$context[ BooleanFormatter::TRUE_VALUE ] = true;
				$context[ BooleanFormatter::FALSE_VALUE ] = false;
			break;
		}

		return $context;
	}

	protected function toLabel( $value, $config )
	{
		if ( is_string( $value ) ) {
			return ucfirst( $value );
		}

		if ( $value === $config[ BooleanFormatter::TRUE_VALUE ] ) {
			return $this->trans( 'Yes' );
		}

		return $this->trans( 'No' );
	}

	protected function toNumeric( $value )
	{
		// @todo Service?
		if ( ! is_numeric( $value ) ) {
			return $value;
		}
		if ( is_string( $value ) && str_contains( $value, '.' ) ) {
			return (float) $value;
		}
		return (int) $value;
	}
}
