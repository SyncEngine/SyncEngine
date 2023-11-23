<?php

namespace App\Task;

use App\Model\TaskModel;
use App\Service\ExecutionContext;

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
					'indexed' => 'Split value into several columns using the key and an index postfix (key#)',
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
					'action' => 'prefixed',
				],
			],
			'remove'    => [
				'label'        => 'Remove merged items?',
				'type'         => 'checkbox',
				'conditionals' => [
					'action' => 'prefixed',
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
			// @todo error?
			return $data;
		}

		switch ( $config['action'] ?? '' ) {
			case 'indexed':

				$prefix = $key . ( $config['postfix'] ?? '' );
				$split  = explode( $config['separator'], $field );

				for ( $i = 0; $i < count( $split ); $i ++ ) {
					$data[ $prefix . $i ] = $split[ $i ];
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
