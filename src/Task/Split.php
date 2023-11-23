<?php

namespace App\Task;

use App\Model\TaskModel;
use App\Service\ExecutionContext;
use App\Service\TagParser;

class Split extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'structure';
		$this->name        = 'Split';
		$this->description = 'Split column into multiple columns';

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
					'value'   => 'Split value into array under the same key',
					'indexed' => 'Split value into several columns using the key and index',
				],
			],
			'separator' => [
				'label' => 'Separator',
				'type'  => 'separator',
			],
			'indexed'   => [
				'label'        => 'Indexed key',
				'type'         => 'text',
				'help'         => 'The template for the new indexed keys.',
				'desc'         => 'Wildcards: {%key%} {%index%}',
				'default'      => '{%key%}_{%index%}',
				'taggable'     => true,
				'conditionals' => [
					'action' => 'indexed',
				],
			],
			'remove'    => [
				'label'        => 'Remove original key?',
				'type'         => 'checkbox',
				'conditionals' => [
					'action' => 'indexed',
				],
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, array $data ): array
	{
		// @todo Use ResourceData?
		$key   = $config['key'];
		$field = $data[ $key ] ?? null;

		if ( empty( $field ) ) {
			return $data;
		}

		if ( empty( $config['separator'] ) ) {
			$context->addError( 'No separator configured' );
			return $data;
		}

		switch ( $config['action'] ?? '' ) {
			case 'indexed':

				$indexed = $config['indexed'] ?? '{%key%}_{%index%}';
				$indexed = str_replace( '{%key%}', $key, $indexed );

				$split  = explode( $config['separator'], $field );

				for ( $i = 0, $num = count( $split ); $i < $num; $i ++ ) {
					$index = str_replace( '{%index%}', $key, $indexed );
					$data[ $index ] = $split[ $i ];
				}

				if ( ! empty( $config['remove'] ) ) {
					unset( $data[ $key ] );
				}
			break;
			case 'value':
				$data[ $key ] = explode( $config['separator'], $field );
			break;
			default:
				$context->addError( 'Invalid action' );
			break;
		}

		return $data;
	}
}
