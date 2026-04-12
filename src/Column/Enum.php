<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\TextColumnType;
use SyncEngine\Exception\InvalidConfigException;
use SyncEngine\Exception\InvalidValueException;
use SyncEngine\Form\Fields\InputFieldType;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\ConditionsValidator;
use SyncEngine\Service\Format\FloatFormatter;
use SyncEngine\Service\Format\IntegerFormatter;
use SyncEngine\Service\Format\StringFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class Enum extends ColumnModel
{
	const MODE_SOFT   = 'soft';
	const MODE_STRICT = 'strict';

	public function __construct()
	{
		parent::__construct();

		$this->type        = TextColumnType::TYPE;
		$this->name        = $this->trans( 'Enumeration' );
		$this->description = $this->trans( 'Predefined select options' );
	}

	public function getFields(): array
	{
		return [
			'options'  => [
				'label'    => $this->trans( 'Options' ),
				'type'     => 'grid',
				'sortable' => true,
				'columns'  => [
					'value' => $this->trans( 'Value' ),
					'label' => $this->trans( 'Label' ),
				],
				'fields'  => [
					'numeric'  => [
						'label' => $this->trans( 'Numeric options' ),
						'type'  => 'switch',
					],
				]
			],
			'mode'     => [
				'label'   => $this->trans( 'Validation mode' ),
				'type'    => 'select',
				'default' => '',
				'choices' => [
					''                => [
						'label'       => $this->trans( 'None' ),
						'description' => $this->trans( 'Use column for type hinting' ),
					],
					self::MODE_SOFT   => [
						'label'       => $this->trans( 'Soft' ),
						'description' => $this->trans( 'Use fallback for invalid values' ),
					],
					self::MODE_STRICT => [
						'label'       => $this->trans( 'Strict' ),
						'description' => $this->trans( 'Throw error on invalid values' ),
					],
				],
			],
			'fallback' => [
				'label'      => $this->trans( 'Fallback value' ),
				'type'       => 'text',
				'help'       => [
					$this->trans( 'Set the fallback value in case it is empty.' ),
					$this->trans( 'Will also be used as fallback in soft mode.' ),
				],
			],
			'parse'    => [
				'label'  => $this->trans( 'Parse before validation' ),
				'fields' => [
					'trim' => [
						'label' => $this->trans( 'Trim text' ),
						'type'  => 'checkbox',
					],
					'case' => [
						'label'   => $this->trans( 'Convert case' ),
						'type'    => 'select',
						'choices' => [
							'ucfirst' => $this->trans( 'Capitalize first' ),
							'ucwords' => $this->trans( 'Capitalize Words' ),
							'lower'   => $this->trans( 'lowercase' ),
							'upper'   => $this->trans( 'UPPERCASE' ),
						],
					],
				],
			],
		];
	}

	public function getInput( array $config = [] ): ?FieldConfigInterface
	{
		$config = $config ?? $this->getConfig();

		$options = array_column( $config['options'] ?? [], 'label', 'value' );

		$field = [
			'type'    => 'select',
			'choices' => $options,
		];

		return new InputFieldType( $field );
	}

	public function format( $value, ?array $config = null, ?ColumnModel $source = null )
	{
		if ( ! empty( $config['numeric'] ) ) {
			foreach ( $config['options'] as $key => $option ) {
				$config['options'][ $key ]['value'] = $this->toNumeric( $option['value'] );
			}
		}

		$formatted = parent::format( $value, $config, $source );

		$options = array_column( $config['options'] ?? [], 'value' );

		if ( ! $options ) {
			throw new InvalidConfigException( $this->trans( 'Invalid enum config' ) );
		}

		$fallback = $config['fallback'] ?? null;
		if ( ConditionsValidator::isEmptyValue( $formatted ) ) {
			$formatted = $fallback;
		}

		if ( ! in_array( $formatted, $options, true ) ) {
			$mode = $config['mode'] ?? '';

			if ( self::MODE_STRICT === $mode ) {
				throw new InvalidValueException(
					$this->trans( 'Invalid value for enum: {formatted}', [ 'formatted' => $formatted ] )
				);
			}
			if ( self::MODE_SOFT === $mode ) {
				return $fallback;
			}
		}

		return $formatted;
	}

	public function initFormatter( $config = [] ): FormatInterface
	{
		if ( ! empty( $config['numeric'] ) ) {
			$hasDecimals = false;
			$options = array_column( $config['options'] ?? [], 'value' );
			foreach ( $options as $value ) {
				if ( is_float( $value ) ) {
					$hasDecimals = true;
					break;
				}
				if ( is_string( $value ) && str_contains( $value, '.' ) ) {
					$hasDecimals = true;
					break;
				}
			}

			if ( $hasDecimals ) {
				return new FloatFormatter();
			}
			return new IntegerFormatter();
		}

		$context = [
			StringFormatter::TRIM => ! empty( $config['trim'] ),
		];

		if ( ! empty( $config['case'] ) ) {
			$context[ StringFormatter::CASE ] = $config['case'];
		}

		return new StringFormatter( $context );
	}

	public function normalize(): array
	{
		$normalize = parent::normalize();

		$normalize['modalProps'] = [
			'size' => 'lg',
		];

		return $normalize;
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
