<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\TextColumnType;
use SyncEngine\Model\ColumnModel;
use SyncEngine\Service\Format\StringFormatter;
use SyncEngine\Service\Interface\FormatInterface;

class Text extends ColumnModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = TextColumnType::TYPE;
		$this->name        = $this->trans( 'Text' );
		$this->description = $this->trans( 'General text column' );
	}

	public function getFields(): array
	{
		return [
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
			'length' => [
				'label'  => $this->trans( 'Limit length?' ),
				'inline' => 'fixed',
				'fields' => [
					'max_length'      => [
						'label' => $this->trans( 'Max' ),
						'type'  => 'number',
					],
					'max_length_unit' => [
						'label'   => $this->trans( 'Unit' ),
						'type'    => 'select',
						'default' => 'chars',
						'choices' => [
							'chars' => $this->trans( 'Characters' ),
							'words' => $this->trans( 'Words' ),
						],
					],
				],
			],
		];
	}

	public function initFormatter( $config = [] ): FormatInterface
	{
		$context = [
			StringFormatter::TRIM => ! empty( $config['trim'] ),
		];

		if ( ! empty( $config['case'] ) ) {
			$context[ StringFormatter::CASE ] = $config['case'];
		}

		if ( ! empty( $config['max_length'] ) ) {
			$context[ StringFormatter::MAX_LENGTH ] = (int) $config['max_length'];
			if ( ! empty( $config['max_length_unit'] ) ) {
				$context[ StringFormatter::MAX_LENGTH_UNIT ] = $config['max_length_unit'];
			}
		}

		return new StringFormatter( $context );
	}
}
