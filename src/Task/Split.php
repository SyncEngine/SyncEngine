<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Task\Type\StructureTaskType;

class Split extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = StructureTaskType::TYPE;
		$this->icon        = 'task-split';
		$this->name        = $this->trans( 'Split' );
		$this->description = $this->trans( 'Split value and/or split column key into multiple' );
	}

	public function getFields(): array
	{
		return [
			'key'         => [
				'label'    => $this->trans( 'Key / Column name' ),
				'help'     => [
					$this->trans( 'The data column name to split' ),
					$this->trans( 'Nested keys are supported: {example}', [ 'example' => 'key.nested_key', ] ),
					$this->trans( 'Leave empty for root iteration' ),
				],
				'type'     => 'text', // @todo Column/Key selection field type.
				'taggable' => true,
			],
			'action'      => [
				'label'   => $this->trans( 'Action' ),
				'type'    => 'select',
				'default' => 'value',
				'choices' => [
					'value' => $this->trans( 'Split value' ),
					'key'   => $this->trans( 'Split into column keys' ),
					'both'  => $this->trans( 'Split value and split into column keys' ),
				],
			],
			'key_method'  => [
				'label'      => $this->trans( 'Column key split method' ),
				'type'       => 'select',
				'choices'    => [
					'columns' => $this->trans( 'Split into column keys by providing custom names' ),
					'indexed' => $this->trans( 'Split into column keys using an indexed name' ),
				],
				'conditions' => [
					'action' => [ 'key', 'both' ],
				],
			],
			'columns'     => [
				'label'      => $this->trans( 'Column key names' ),
				'type'       => 'grid',
				'columns'    => [
					'index' => $this->trans( 'Current value index/key (optional)' ),
					'key'   => $this->trans( 'New column key name' ),
				],
				'taggable'   => true,
				'conditions' => [
					'action'     => [ 'key', 'both' ],
					'key_method' => 'columns',
				],
			],
			'index_key'   => [
				'label'      => $this->trans( 'Indexed key' ),
				'type'       => 'text',
				'help'       => $this->trans( 'The template for the new indexed keys.' ),
				'desc'       => $this->trans( 'Wildcards: {wildcards}', [ 'wildcards' => '{*key*} {*index*}' ] ),
				// @todo Convert this to Tags (Needs big refactor in Execute service.
				'default'    => '{*key*}_{*index*}',
				'taggable'   => true,
				'conditions' => [
					'action'     => [ 'key', 'both' ],
					'key_method' => 'indexed',
				],
			],
			'index_start' => [
				'label'       => $this->trans( 'Index starts with' ),
				'type'        => 'number',
				'placeholder' => '0',
				'conditions'  => [
					'action'     => [ 'key', 'both' ],
					'key_method' => 'indexed',
				],
			],
			'remove'      => [
				'label'      => $this->trans( 'Remove original key(s)?' ),
				'type'       => 'switch',
				'conditions' => [
					'action' => [ 'key', 'both' ],
				],
			],
			'separator'   => [
				'label'        => $this->trans( 'Separator' ),
				'type'         => 'select',
				'choices'      => [
					','       => $this->trans( 'Comma' ) . ' (,)',
					';'       => $this->trans( 'Semicolon' ) . ' (;)',
					' '       => $this->trans( 'Space' ) . ' (\n)',
					'{*tab*}' => $this->trans( 'Tab' ),
					'{*nl*}'  => $this->trans( 'New line' ) . ' (\n)',
				],
				'customizable' => true,
				'conditions'   => [
					'action' => [ 'value', 'both' ],
				],
			],
		];
	}

	public function execute( array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['key'] ) ) {
			$context->addError( $this->trans( 'No key configured' ) );

			return $data;
		}

		if ( empty( $config['action'] ) ) {
			$context->addError( $this->trans( 'No action configured' ) );

			return $data;
		}

		$key   = $config['key'];
		$value = $data->get( $key );

		if ( empty( $value ) ) {
			return $data;
		}

		$action = $config['action'];

		if ( is_array( $value ) && str_contains( $key, '[]' ) ) {
			/*foreach ( $value as $index => $val ) {
				// @todo Support loop structure.
			}*/
			$context->addError( $this->trans( 'Loop key not supported' ) );

			return $data;
		}

		if ( 'value' === $action || 'both' === $action ) {
			if ( ! is_string( $value ) ) {
				$context->addError( $this->trans( 'Value is not splittable into list' ) );

				return $data;
			}

			$separator = match ( $config['separator'] ?? '' ) {
				'{*nl*}' => "\n",
				'{*tab*}' => "	",
				default => $config['separator'] ?? '',
			};

			$value = explode( $separator, $value );
		}

		if ( 'key' === $action || 'both' === $action ) {

			if ( ! is_array( $value ) ) {
				$context->addError( $this->trans( 'Value is not splittable into keys' ) );

				return $data;
			}

			if ( ! empty( $config['remove'] ) ) {
				$data->unset( $key );
			}

			switch ( $config['key_method'] ?? '' ) {
				case 'indexed':

					$indexed = $config['index_key'] ?? '{*key*}_{*index*}';
					$indexed = str_replace( '{*key*}', $key, $indexed );

					$start = (int) ( $config['index_start'] ?? 0 );
					for ( $i = $start, $num = $start + count( $value ); $i < $num; $i ++ ) {
						$index_key = str_replace( '{*index*}', $i, $indexed );

						$data->set( $value[ $i - $start ], $index_key );
					}

				break;
				case 'columns':

					if ( empty( $config['columns'] ) ) {
						$context->addError( $this->trans( 'No columns configured' ) );

						return $data;
					}

					$columns = $config['columns'];
					for ( $i = 0, $count = count( $columns ); $i < $count; $i ++ ) {
						$column = $columns[ $i ];
						if ( ! isset( $column['key'] ) ) {
							continue;
						}
						$name  = $column['key'];
						$index = $column['index'] ?? $i;

						if ( isset( $value[ $index ] ) ) {
							$data->set( $value[ $index ], $name );
						}
					}

				break;
			}
		} else {
			$data->set( $value, $key );
		}

		return $data;
	}
}
