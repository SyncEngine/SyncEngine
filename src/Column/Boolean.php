<?php

namespace SyncEngine\Column;

use SyncEngine\Column\Type\BooleanColumnType;
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
					'bool'   => $this->trans( 'Boolean' ) . ' (true/false)',
					'int'    => $this->trans( 'Integer' ) . ' (1/0)',
					'string' => $this->trans( 'Custom text' ),
				],
			],
			'true_value'   => [
				'label'      => $this->trans( 'True value' ),
				'type'       => 'text',
				'conditions' => [ 'format' => 'string' ],
			],
			'false_value'  => [
				'label'      => $this->trans( 'False value' ),
				'type'       => 'text',
				'conditions' => [ 'format' => 'string' ],
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

	public function initFormatter( $config = [] ): FormatInterface
	{
		$context[ BooleanFormatter::FORMAT ] = $config['format'] ?? 'bool';
		if ( 'string' === $context[ BooleanFormatter::FORMAT ] ) {
			$context[ BooleanFormatter::TRUE_VALUE ] = $config['true_value'] ?? '';
			$context[ BooleanFormatter::FALSE_VALUE ] = $config['false_value'] ?? '';
		} elseif ( 'int' === $context[ BooleanFormatter::FORMAT ] ) {
			$config['true_value'] = 1;
			$config['false_value'] = 0;
		} else {
			$config['true_value'] = true;
			$config['false_value'] = false;
		}

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

		return new BooleanFormatter( $context );
	}
}
