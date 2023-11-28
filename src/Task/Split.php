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
			'key'         => [
				'label'    => 'Key',
				'type'     => 'text', // @todo Column/Key selection field type.
				'taggable' => true,
			],
			'action'      => [
				'label'   => 'Action',
				'type'    => 'select',
				'default' => 'value',
				'choices' => [
					'value' => 'Split value',
					'key'   => 'Split into keys',
					'both'  => 'Split value and split into keys',
				],
			],
			'key_method'   => [
				'label'        => 'Key split method',
				'type'         => 'select',
				'choices'      => [
					'columns' => 'Split keys by providing custom names',
					'indexed' => 'Split keys using an indexed name',
				],
				'conditionals' => [
					'action' => [ 'key', 'both' ],
				],
			],
			'columns'      => [
				'label'        => 'New column names',
				'type'         => 'columns',
				'columns'      => [
					'index' => 'Current index/key (optional)',
					'key'   => 'New column key name',
				],
				'taggable'     => true,
				'conditionals' => [
					'action'     => [ 'key', 'both' ],
					'key_method' => 'columns',
				],
			],
			'index_key'   => [
				'label'        => 'Indexed key',
				'type'         => 'text',
				'help'         => 'The template for the new indexed keys.',
				'desc'         => 'Wildcards: {%key%} {%index%}', // @todo Convert this to Tags (Needs big refactor in Execute service.
				'default'      => '{%key%}_{%index%}',
				'taggable'     => true,
				'conditionals' => [
					'action'     => [ 'key', 'both' ],
					'key_method' => 'indexed',
				],
			],
			'index_start' => [
				'label'        => 'Index starts with',
				'type'         => 'number',
				'placeholder'  => '0',
				'conditionals' => [
					'action'     => [ 'key', 'both' ],
					'key_method' => 'indexed',
				],
			],
			'remove'      => [
				'label'        => 'Remove original key(s)?',
				'type'         => 'checkbox',
				'conditionals' => [
					'action' => [ 'key', 'both' ],
				],
			],
			'separator'   => [
				'label'        => 'Separator',
				'type'         => 'select',
				'choices'      => [
					','       => 'Comma (,)',
					';'       => 'Semicolon (;)',
					'{%tab%}' => 'Tab',
					'{%nl%}'  => 'New line (\n)',
				],
				'customizable' => true,
				'conditionals' => [
					'action' => [ 'value', 'both' ],
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

		if ( empty( $config['action'] ) ) {
			$context->addError( 'No action configured' );

			return $data;
		}

		$resource = new ResourceData( $data );
		$key      = $config['key'];
		$value    = $resource->get( $key );

		if ( empty( $value ) ) {
			return $data;
		}

		$action   = $config['action'];

		if ( is_array( $value ) && str_contains( $key, '[]' ) ) {
			/*foreach ( $value as $index => $val ) {
				// @todo Support loop structure.
			}*/
			$context->addError( 'Loop key not supported' );

			return $data;
		}


		if ( 'value' === $action || 'both' === $action ) {
			if ( ! is_string( $value ) ) {
				$context->addError( 'Value is not splittable into list' );

				return $data;
			}

			$separator = match ( $config['separator'] ?? '' ) {
				'{%nl%}'  => "\n",
				'{%tab%}' => "	",
				default   => $config['separator'] ?? '',
			};

			$value = explode( $separator, $value );
		}

		if ( 'key' === $action || 'both' === $action ) {

			if ( ! is_array( $value ) ) {
				$context->addError( 'Value is not splittable into keys' );

				return $data;
			}

			if ( ! empty( $config['remove'] ) ) {
				$resource->unset( $key );
			}

			switch ( $config['key_method'] ?? '' ) {
				case 'indexed':

					$indexed = $config['index_key'] ?? '{%key%}_{%index%}';
					$indexed = str_replace( '{%key%}', $key, $indexed );

					$start = (int) ( $config['index_start'] ?? 0 );
					for ( $i = $start, $num = $start + count( $value ); $i < $num; $i ++ ) {
						$index_key = str_replace( '{%index%}', $i, $indexed );

						$resource->set( $value[ $i - $start ], $index_key );
					}

				break;
				case 'columns':

					if ( empty( $config['columns'] ) ) {
						$context->addError( 'No columns configured' );

						return $data;
					}

					$columns = $config['columns'];
					for ( $i = 0, $count = count( $columns ); $i < $count; $i++ ) {
						$column = $columns[ $i ];
						if ( ! isset( $column['key'] ) ) {
							continue;
						}
						$name  = $column['key'];
						$index = $column['index'] ?? $i;

						if ( isset( $value[ $index ] ) ) {
							$resource->set( $value[ $index ], $name );
						}
					}

				break;
			}
		} else {
			$resource->set( $value, $key );
		}

		return $resource->get();
	}
}
