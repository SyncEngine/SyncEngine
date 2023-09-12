<?php

namespace App\Task;

use App\Model\TaskModel;
use App\Service\ExecutionContext;

class Merge extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'structure';
		$this->name        = 'Merge';
		$this->description = 'Merge columns into single column';

		parent::__construct();
	}

	public function getFields(): array
	{
		return [
			'key'       => [
				'label'    => 'Key',
				'type'     => 'text', // @todo Column/Key selection field type.
				'taggable' => true,
			],
			'action'    => [
				'label'   => 'Action',
				'type'    => 'select',
				'default' => 'value',
				'choices' => [
					'value'   => 'Merge key value',
					'indexed' => 'Merge columns with key as prefix ending with an index (key#)',
				],
			],
			'separator' => [
				'label' => 'Separator',
				'type'  => 'separator',
			],
			'postfix'   => [
				'label'        => 'Postfix',
				'type'         => 'text',
				'default'      => '__',
				'conditionals' => [
					'action' => 'indexed',
				],
			],
			'remove'    => [
				'label'        => 'Remove merged items?',
				'type'         => 'checkbox',
				'conditionals' => [
					'action' => 'indexed',
				],
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
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
				$context->addError( 'Invalid action' );
			break;
		}

		return $data;
	}
}
