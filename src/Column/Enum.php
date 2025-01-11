<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\TextColumnType;
use SyncEngine\Exception\InvalidConfigException;
use SyncEngine\Exception\InvalidValueException;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Format\StringFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class Enum extends ColumnModel
{
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
			'options' => [
				'label' => $this->trans( 'Options' ),
				'type'  => 'grid',
				'columns' => [
					'value' => $this->trans( 'Value' ),
					'label' => $this->trans( 'Label' ),
				],
			],
			'parse' => [
				'label'  => $this->trans( 'Parse before validation' ),
				'fields' => [
					'trim'   => [
						'label' => $this->trans( 'Trim text' ),
						'type'  => 'checkbox',
					],
					'case'   => [
						'label'   => $this->trans( 'Convert case' ),
						'type'    => 'select',
						'choices' => [
							'ucfirst' => $this->trans( 'Capitalize first' ),
							'ucwords' => $this->trans( 'Capitalize Words' ),
							'lower'   => $this->trans( 'lowercase' ),
							'upper'   => $this->trans( 'UPPERCASE' ),
						],
					],
				]
			]
		];
	}

	public function format( $value, ?array $config = null, ?ColumnModel $source = null )
	{
		$formatted = parent::format( $value, $config, $source );

		$options = $config['options'] ?? [];

		$values = array_column( $options, 'value' );

		if ( ! $values ) {
			throw new InvalidConfigException( $this->trans( 'Invalid enum config' ) );
		}

		if ( ! in_array( $value, $values, true ) ) {
			throw new InvalidValueException( $this->trans( 'Invalid value: {formatted}', [ 'formatted' => $formatted ] ) );
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
