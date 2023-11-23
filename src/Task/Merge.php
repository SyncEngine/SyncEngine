<?php

namespace App\Task;

use App\Model\TaskModel;
use App\Service\ExecutionContext;
use App\Service\ResourceData;

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
					'value'   => 'Merge value into string under the same key',
					'indexed' => 'Merge columns with key as prefix ending with an index (key#)',
				],
			],
			'separator' => [
				'label' => 'Separator',
				'type'  => 'text',
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
				'label'        => 'Remove original key(s)?',
				'type'         => 'checkbox',
				'conditionals' => [
					'action' => 'indexed',
				],
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, array $data ): array
	{
		if ( empty( $config['key'] ) ) {
			$context->addError( 'No key configured' );
			return $data;
		}

		if ( empty( $config['separator'] ) ) {
			$context->addError( 'No separator configured' );
			return $data;
		}

		$resource = new ResourceData( $data ?? [] );
		$key      = $config['key'];
		$values = [];

		switch ( $config['action'] ?? '' ) {
			case 'indexed':
				$search = $key . ( $config['postfix'] ?? '' );
				$i      = 0;
				while ( $value = $resource[ $search . $i ] ) {
					$values[] = $value;
					if ( ! empty( $config['remove'] ) ) {
						unset( $resource[ $search . $i ] );
					}
				}
				$resource[ $key ] = implode( $config['separator'] ?? '', $values );
			break;
			case 'value':
			default:
				$value = $resource[ $key ];
				if ( $value && is_array( $value ) ) {
					$resource[ $key ] = implode( $config['separator'] ?? '', $value );
				}
			break;
		}

		return $resource->get();
	}
}
