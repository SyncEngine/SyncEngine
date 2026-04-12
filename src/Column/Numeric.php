<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\NumericColumnType;
use SyncEngine\Form\Fields\InputFieldType;
use SyncEngine\Form\Fields\Interface\FieldConfigInterface;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Format\FloatFormatter;
use SyncEngine\Service\Format\IntegerFormatter;
use SyncEngine\Service\Format\NumberFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class Numeric extends ColumnModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = NumericColumnType::TYPE;
		$this->name        = $this->trans( 'Number' );
		$this->description = $this->trans( 'Numeric column' );
	}

	public function getFields(): array
	{
		return [
			'type'                => [
				'label'   => $this->trans( 'Type' ),
				'type'    => 'select',
				'default' => 'raw',
				'help'    => [
					$this->trans( 'A raw number can be used for calculations and number storage.' ),
					$this->trans( 'A formatted number can be used for display.' ),
				],
				'choices' => [
					'raw'    => [
						'label'       => $this->trans( 'Raw number' ),
						'description' => $this->trans( 'Format to a number type.' ) . ' (int/float)',
					],
					'format' => [
						'label'       => $this->trans( 'Formatted' ),
						'description' => $this->trans( 'Format to a numeric string.' ),
					],
				],
			],
			'round'               => [
				'label'   => $this->trans( 'Rounding' ),
				'type'    => 'select',
				'choices' => [
					'up'   => $this->trans( 'Round halves up' ),
					'down' => $this->trans( 'Round halves down' ),
					'even' => $this->trans( 'Round halves to even numbers' ),
					'odd'  => $this->trans( 'Round halves to odd numbers' ),
				],
			],
			'decimals'            => [
				'label'       => $this->trans( 'Decimals' ),
				'type'        => 'number',
				'placeholder' => 0,
			],
			'trim'                => [
				'label'      => $this->trans( 'Trim trailing zeros (0)' ),
				'type'       => 'switch',
				'conditions' => [ 'type' => 'format' ],
			],
			'decimal_separator'   => [
				'label'        => $this->trans( 'Decimal separator' ),
				'type'         => 'select',
				'customizable' => true,
				'taggable'     => true,
				'default'      => '.',
				'choices'      => [
					'.' => '.',
					',' => ',',
				],
				'conditions'   => [ 'type' => 'format' ],
			],
			'thousands_separator' => [
				'label'        => $this->trans( 'Thousands separator' ),
				'type'         => 'select',
				'customizable' => true,
				'taggable'     => true,
				'default'      => ',',
				'choices'      => [
					','  => ',',
					'.'  => '.',
					''   => '{ ' . $this->trans( 'none' ) . ' }',
					' '  => '{ ' . $this->trans( 'space' ) . ' }',
					'\'' => '\'',
				],
				'conditions'   => [ 'type' => 'format' ],
			],
		];
	}

	public function getInput( array $config = [] ): ?FieldConfigInterface
	{
		$config = $config ?? $this->getConfig();

		$field = [
			'type' => 'number',
		];

		if ( ! empty( $config['decimals'] ) ) {
			$field['step'] = '0.' . str_repeat( '0', $config['decimals'] - 1 ) . '1';
		}

		return new InputFieldType( $field );
	}

	public function initFormatter( array $config = [] ): FormatInterface
	{
		switch ( $config['type'] ?? '' ) {
			case 'format':
				$context = [
					NumberFormatter::DECIMALS            => $config['decimals'] ?? 0,
					NumberFormatter::DECIMAL_SEPARATOR   => $config['decimal_separator'] ?? '.',
					NumberFormatter::THOUSANDS_SEPARATOR => $config['thousands_separator'] ?? ',',
					NumberFormatter::ROUND               => $config['round'] ?? null,
					NumberFormatter::TRIM                => $config['trim'] ?? null,
				];

				return new NumberFormatter( $context );
			default:
				$context = [];
				if ( ! empty( $config['round'] ) ) {
					$context[ FloatFormatter::MODE ] = $config['round'];
				}
				if ( ! empty( $config['decimals'] ) ) {
					$context[ FloatFormatter::PRECISION ] = (int) $config['decimals'];

					return new FloatFormatter( $context );
				}

				$context[ FloatFormatter::PRECISION ] = 0;

				return new IntegerFormatter( $context );
		}
	}
}
