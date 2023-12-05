<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\ResourceData;

class Merge extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'structure';
		$this->name        = $this->trans( 'Merge' );
		$this->description = $this->trans( 'Merge columns and/or values' );

		parent::__construct();
	}

	public function getFields(): array
	{
		return [
			'key'          => [
				'label'    => $this->trans( 'Key' ),
				'type'     => 'text', // @todo Column/Key selection field type.
				'taggable' => true,
			],
			'action'       => [
				'label'   => $this->trans( 'Action' ),
				'type'    => 'select',
				'default' => 'value',
				'choices' => [
					'value' => $this->trans( 'Merge values' ),
					'key'   => $this->trans( 'Merge keys' ),
					'both'  => $this->trans( 'Merge both' ),
				],
			],
			'key_method'   => [
				'label'        => $this->trans( 'Key merge method' ),
				'type'         => 'select',
				'choices'      => [
					'columns' => $this->trans( 'Merge key names' ),
					'indexed' => $this->trans( 'Merge keys using an indexed name' ),
				],
				'conditionals' => [
					'action' => [ 'key', 'both' ],
				],
			],
			'columns'      => [
				'label'        => $this->trans( 'Column keys that need to be merged' ),
				'type'         => 'columns',
				'columns'      => [ 'key' => 'Key name' ],
				'taggable'     => true,
				'conditionals' => [
					'action'     => [ 'key', 'both' ],
					'key_method' => 'columns',
				],
			],
			'index_key'    => [
				'label'        => $this->trans( 'Indexed key to search for and merge' ),
				'type'         => 'text',
				'help'         => $this->trans( 'The template for the indexed keys' ),
				'desc'         => $this->trans( 'Wildcards: {%key%} {%index%}' ),
				// @todo Convert this to Tags (Needs big refactor in Execute service.
				'default'      => '{%key%}_{%index%}',
				'taggable'     => true,
				'conditionals' => [
					'action'     => [ 'key', 'both' ],
					'key_method' => 'indexed',
				],
			],
			'index_start'  => [
				'label'        => $this->trans( 'Index starts with' ),
				'type'         => 'number',
				'placeholder'  => '0',
				'conditionals' => [
					'action'     => [ 'key', 'both' ],
					'key_method' => 'indexed',
				],
			],
			'remove'       => [
				'label'        => $this->trans( 'Remove original key(s)?' ),
				'type'         => 'checkbox',
				'conditionals' => [
					'action' => [ 'key', 'both' ],
				],
			],
			'separator'    => [
				'label'        => $this->trans( 'Separator' ),
				'type'         => 'select',
				'choices'      => [
					','        => $this->trans( 'Comma' ) . ' (,)',
					';'        => $this->trans( 'Semicolon' ) . ' (;)',
					'{%tab%}'  => $this->trans( 'Tab' ),
					'{%nl%}'   => $this->trans( 'New line' ) . ' (\n)',
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
			$context->addError( $this->trans( 'No key configured' ) );

			return $data;
		}

		$resource = new ResourceData( $data ?? [] );
		$key      = $config['key'];
		// @todo Support loop structure.

		$action = $config['action'] ?? 'value';

		$values = $resource->get( $key );

		if ( 'both' === $action || 'key' === $action ) {
			switch ( $config['key_method'] ?? '' ) {
				case 'indexed':
					$values = [];

					$indexed = $config['index_key'] ?? '{%key%}_{%index%}';
					$indexed = str_replace( '{%key%}', $key, $indexed );

					$start = (int) ( $config['index_start'] ?? 0 );
					for (
						$i = $start;
							$index_key = str_replace( '{%index%}', $i, $indexed ),
							$value = $resource->get( $index_key ),
							null !== $value;
						$i ++
					) {
						$values[ $i ] = $value;
						if ( ! empty( $config['remove'] ) ) {
							$resource->unset( $index_key );
						}
					}
				break;
				case 'columns':
					if ( empty( $config['columns'] ) ) {
						$context->addError( $this->trans( 'No columns defined' ) );

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
				break;
				default:
					$context->addError( $this->trans( 'No key method selected' ) );
				break;
			}
		}


		if ( 'value' === $action || 'both' === $action ) {
			$separator = match ( $config['separator'] ?? '' ) {
				'{%nl%}' => "\n",
				'{%tab%}' => "	",
				default => $config['separator'] ?? '',
			};

			if ( is_array( $values ) ) {
				$values = implode( $separator, $values );
			}
		}

		$resource->set( $values, $key );

		return $resource->get();
	}
}
