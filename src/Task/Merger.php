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
					'value'    => 'Merge key value',
					'prefixed' => 'Merge columns with key as prefix ending with an index (key#)',
				]
			],
			'separator' => [
				'type' => 'separator',
			],
			'postfix' => [
				'type' => 'text',
				'default' => '__',
				'conditionals' => [
					'action' => 'prefixed',
				]
			],
		];
	}

	public function execute( array $config, array $data ): array
	{
		$key    = $config['key'];
		$values = [];

		switch ( $config['action'] ?? '' ) {
			case 'prefixed':
				$search = $key . ( $config['postfix'] ?? '' );
				$i      = 0;
				while ( isset( $data[ $search . $i ] ) ) {
					$values[] = $data[ $search . $i ];
					unset( $data[ $search . $i ] );
				}
				$data[ $key ] = implode( $config['separator'], $values );
				break;
			case 'value':
				if ( is_array( $data[ $key ] ) ) {
					$data[ $key ] = implode( $config['separator'], $data[ $key ] );
				}
			default:
				break;
		}

		return $data;
	}
}
