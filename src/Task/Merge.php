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
					'indexed' => 'Merge columns into string using the key and index',
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
			],
			'index_key' => [
				'label'        => 'Indexed key to search for',
				'type'         => 'text',
				'help'         => 'The template for the indexed keys.',
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
					$values[] = $value;
					if ( ! empty( $config['remove'] ) ) {
						unset( $resource[ $index_key ] );
					}
				}
				$resource[ $key ] = implode( $separator, $values );
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
