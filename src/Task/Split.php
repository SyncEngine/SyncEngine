<?php

namespace App\Task;

use App\Model\TaskModel;
use App\Service\ExecutionContext;
use App\Service\ResourceData;
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
				'type'  => 'text',
			],
			'index_key'   => [
				'label'        => 'Indexed key',
				'type'         => 'text',
				'help'         => 'The template for the new indexed keys.',
				'desc'         => 'Wildcards: {%key%} {%index%}', // @todo Convert this to Tags (Needs big refactor in Execute service.
				'default'      => '{%key%}_{%index%}',
				'taggable'     => true,
				'conditionals' => [
					'action' => 'indexed',
				],
			],
			'index_start' => [
				'label'        => 'Index starts with',
				'type'         => 'number',
				'placeholder'  => '0',
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
		$value    = $resource[ $key ] ?? null;

		if ( empty( $value ) ) {
			return $data;
		}

		if ( is_array( $value ) && str_contains( $key, '[]') ) {
			/*foreach ( $value as $index => $val ) {
				// @todo Support loop structure.
			}*/
			$context->addError( 'Loop key not supported' );
			return $data;
		}

		if ( ! is_string( $value ) ) {
			$context->addError( 'Value is not splittable' );
			return $data;
		}

		$split = explode( $config['separator'], $value );

		switch ( $config['action'] ?? '' ) {
			case 'indexed':

				$indexed = $config['index_key'] ?? '{%key%}_{%index%}';
				$indexed = str_replace( '{%key%}', $key, $indexed );

				$start = (int) ( $config['index_start'] ?? 0 );
				for ( $i = $start, $num = $start + count( $split ); $i < $num; $i ++ ) {
					$index_key = str_replace( '{%index%}', $i, $indexed );
					$resource[ $index_key ] = $split[ $i - $start ];
				}

				if ( ! empty( $config['remove'] ) ) {
					unset( $resource[ $key ] );
				}
			break;
			case 'value':
			default:
				$resource[ $key ] = $split;
			break;
		}

		return $resource->get();
	}
}
