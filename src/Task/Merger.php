<?php

namespace App\Task;

use App\Model\TaskModel;

class Merger extends TaskModel
{
	public function __construct()
	{
		$this->type = 'merger';
		$this->name = 'Merger';
		$this->description = 'Merge columns into single column';

		parent::__construct();
	}

	public function getFields(): array {
		return [
			'key' => [
				'type' => 'text', // @todo Column/Key selection field type.
			],
			'action' => [
				'type' => 'select',
				'default' => 'value',
				'choices' => [
					'value'   => 'Merge key value',
					'indexed' => 'Merge columns with key as prefix ending with an index (key#)',
				]
			],
			'separator' => [
				'type' => 'separator',
			],
			'postfix' => [
				'type' => 'text',
				'default' => '__',
				'conditionals' => [
					'action' => 'indexed',
				]
			],
			'remove' => [
				'type' => 'checkbox',
				'label' => 'Remove merged items?',
				'conditionals' => [
					'action' => 'indexed',
				]
			],
		];
	}

	public function execute( array $config, array $data ): array
	{
		$key    = $config['key'];
		$values = [];

		switch ( $config['action'] ?? '' ) {
			case 'indexed':
				$search = $key . ( $config['postfix'] ?? '' );
				$i      = 0;
				while ( isset( $data[ $search . $i ] ) ) {
					$values[] = $data[ $search . $i ];
					if ( ! empty( $config['remove'] ) ) {
						unset( $data[ $search . $i ] );
					}
				}
				$data[ $key ] = implode( $config['separator'] ?? '', $values );
				break;
			case 'value':
				if ( isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) {
					$data[ $key ] = implode( $config['separator'] ?? '', $data[ $key ] );
				}
				break;
			default:
				break;
		}

		return $data;
	}
}
