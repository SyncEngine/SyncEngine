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
			'action'    => [
				'label'   => 'Action',
				'type'    => 'select',
				'default' => 'value',
				'choices' => [
					'value'   => 'Merge value into string under the same key',
					'columns' => 'Merge column keys',
					'indexed' => 'Merge columns using the key and index',
				],
			],
			'key'       => [
				'label'    => 'Key',
				'type'     => 'text', // @todo Column/Key selection field type.
				'taggable' => true,
			],
			'columns'   => [
				'label'   => 'Column keys that need to be merged',
				'type'    => 'columns',
				'columns' => [ 'key' => 'Key' ],
				'taggable' => true,
				'conditionals' => [
					'action' => 'columns',
				],
			],
			'index_key' => [
				'label'        => 'Indexed key to search for and merge',
				'type'         => 'text',
				'help'         => 'The template for the indexed keys',
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
				'label'        => 'Remove original column key(s)?',
				'type'         => 'checkbox',
				'conditionals' => [
					'action' => [ 'indexed', 'columns' ],
				],
			],
			'separator'   => [
				'label'        => 'Separator',
				'type'         => 'select',
				'help'         => 'For columns, leave empty if you want to merge into a single list',
				'choices'      => [
					','        => 'Comma (,)',
					';'        => 'Semicolon (;)',
					'{%tab%}'  => 'Tab',
					'{%nl%}'   => 'New line (\n)',
				],
				'customizable' => true,
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, array $data ): array
	{
		if ( empty( $config['key'] ) ) {
			$context->addError( 'No key configured' );

			return $data;
		}

		$resource = new ResourceData( $data ?? [] );
		$key      = $config['key'];
		// @todo Support loop structure.

		$separator = match ( $config['separator'] ?? '' ) {
			'{%nl%}'  => "\n",
			'{%tab%}' => "	",
			default   => $config['separator'] ?? '',
		};

		switch ( $config['action'] ?? '' ) {
			case 'indexed':
				$values = [];

				$indexed = $config['index_key'] ?? '{%key%}_{%index%}';
				$indexed = str_replace( '{%key%}', $key, $indexed );

				$start = (int) ( $config['index_start'] ?? 0 );
				for (
					$i = $start;
						$index_key = str_replace( '{%index%}', $i, $indexed ),
						$value = $resource[ $index_key ],
						null !== $value;
					$i++
				) {
					$values[ $i ] = $value;
					if ( ! empty( $config['remove'] ) ) {
						unset( $resource[ $index_key ] );
					}
				}
				$resource[ $key ] = $separator ? implode( $separator, $values ) : $values;
			break;
			case 'columns':
				if ( empty( $config['columns'] ) ) {
					$context->addError( 'No columns defined' );
					return $data;
				}

				$values = [];
				foreach ( array_column( $config['columns'], 'key' ) as $column ) {
					$values[] = $resource->get( $column );

					if ( ! empty( $config['remove'] ) ) {
						$resource->unset( $column );
					}
				}
				$values = array_filter( $values );
				if ( $values ) {
					$resource[ $key ] = $separator ? implode( $separator, $values ) : $values;
				}
			break;
			case 'value':
			default:
				$value = $resource[ $key ];
				if ( $value && is_array( $value ) ) {
					$resource[ $key ] = implode( $separator, $value );
				}
			break;
		}

		return $resource->get();
	}
}
