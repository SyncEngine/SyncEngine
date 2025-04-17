<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\NumericColumnType;
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
				'choices' => [
					'raw'    => [
						'label'       => $this->trans( 'Raw' ),
						'description' => $this->trans( 'Format to a number type.' ),
					],
					'format' => [
						'label'       => $this->trans( 'Formatted' ),
						'description' => $this->trans( 'Format to a numeric string.' ),
					],
				],
			],
			'decimals'            => [
				'label'       => $this->trans( 'Decimals' ),
				'type'        => 'number',
				'placeholder' => 0,
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
		$field = [
			'type' => 'number',
		];

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
