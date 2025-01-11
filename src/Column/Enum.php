<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\TextColumnType;
use SyncEngine\Exception\InvalidConfigException;
use SyncEngine\Exception\InvalidValueException;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Conditions;
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
			],
			'mode'     => [
				'label'   => $this->trans( 'Validation mode' ),
				'type'    => 'select',
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
						'description' => $this->trans( 'Throws error on invalid values' ),
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
				'conditions' => [ 'mode' => 'soft' ],
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

	public function format( $value, ?array $config = null, ?ColumnModel $source = null )
	{
		$formatted = parent::format( $value, $config, $source );

		$options = array_column( $config['options'] ?? [], 'value' );

		if ( ! $options ) {
			throw new InvalidConfigException( $this->trans( 'Invalid enum config' ) );
		}

		$fallback = $config['fallback'] ?? null;
		if ( Conditions::isEmptyValue( $formatted ) ) {
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
}
