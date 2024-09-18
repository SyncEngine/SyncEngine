<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Task\Type\StructureTaskType;

class Merge extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = StructureTaskType::TYPE;
		$this->name        = $this->trans( 'Merge' );
		$this->description = $this->trans( 'Merge columns and/or values' );
	}

	public function getFields(): array
	{
		return [
			'key'            => [
				'label'    => $this->trans( 'Key / Column name' ),
				'type'     => 'text', // @todo Column/Key selection field type.
				'help'     => [
					$this->trans( 'The data column name to merge' ),
					$this->trans( 'Nested keys are supported: {example}', [ 'example' => 'key.nested_key', ] ),
				],
				'taggable' => true,
				'required' => true,
			],
			'action'         => [
				'label'    => $this->trans( 'Action' ),
				'type'     => 'select',
				'default'  => 'value',
				'required' => true,
				'choices'  => [
					'value' => $this->trans( 'Merge values of a column into a single value' ),
					'key'   => $this->trans( 'Merge columns' ),
					'both'  => $this->trans( 'Merge both' ),
				],
			],
			'key_method'     => [
				'label'      => $this->trans( 'Key merge method' ),
				'type'       => 'select',
				'required'   => true,
				'choices'    => [
					'columns' => $this->trans( 'Merge column key names' ),
					'indexed' => $this->trans( 'Merge column keys using an indexed name' ),
				],
				'conditions' => [
					'action' => [ 'key', 'both' ],
				],
			],
			'columns'        => [
				'label'      => $this->trans( 'Column keys that need to be merged' ),
				'type'       => 'grid',
				'columns'    => [ 'key' => 'Key name' ],
				'taggable'   => true,
				'sortable'   => true,
				'conditions' => [
					'action'     => [ 'key', 'both' ],
					'key_method' => 'columns',
				],
			],
			'index_key'      => [
				'label'       => $this->trans( 'Indexed key to search for and merge' ),
				'type'        => 'text',
				'help'        => $this->trans( 'The template for the indexed keys' ),
				'description' => $this->trans( 'Wildcards: {wildcards}', [ 'wildcards' => '{*key*} {*index*}' ] ),
				// @todo Convert this to Tags (Needs big refactor in Execute service.
				'default'     => '{*key*}_{*index*}',
				'taggable'    => true,
				'conditions'  => [
					'action'     => [ 'key', 'both' ],
					'key_method' => 'indexed',
				],
			],
			'index_start'    => [
				'label'       => $this->trans( 'Index starts with' ),
				'type'        => 'number',
				'placeholder' => '0',
				'conditions'  => [
					'action'     => [ 'key', 'both' ],
					'key_method' => 'indexed',
				],
			],
			'merge_method'   => [
				'label'      => $this->trans( 'Method to merge values from multiple columns' ),
				'type'       => 'select',
				'default'    => 'list',
				'choices'    => [
					'list'    => $this->trans( 'List, reach column value will be appended' ),
					// array_merge
					'merge'   => $this->trans( 'Collection, the latter column values merges into the former' ),
					// array_replace
					'replace' => $this->trans( 'Collection, the latter column values replaces the former' ),
					// only add list items (numeric keys) or add items that do not yet exist.
					'append'  => $this->trans( 'Collection, the latter column values append into the former' ),
				],
				'conditions' => [
					'action' => [ 'key', 'both' ],
				],
			],
			'separator'      => [
				'label'        => $this->trans( 'Separator' ),
				'type'         => 'select',
				'choices'      => [
					','       => $this->trans( 'Comma' ) . ' (,)',
					';'       => $this->trans( 'Semicolon' ) . ' (;)',
					'{*tab*}' => $this->trans( 'Tab' ),
					'{*nl*}'  => $this->trans( 'New line' ) . ' (\n)',
				],
				'customizable' => true,
				'conditions'   => [
					'action' => [ 'value', 'both' ],
				],
			],
			'value_template' => [
				'label'       => $this->trans( 'Value template' ),
				'type'        => 'text',
				'placeholder' => '{*value*}',
				'help'        => $this->trans( 'The template for each separate value to be merged' ),
				'description' => $this->trans( 'Wildcards: {wildcards}', [ 'wildcards' => '{*key*} {*index*} {*value*} {*nl*} {*tab*}' ] ),
				'taggable'    => true,
				'conditions'  => [
					'action' => [ 'value', 'both' ],
				],
			],
			'remove'         => [
				'label'      => $this->trans( 'Remove original column key(s)?' ),
				'type'       => 'checkbox',
				'conditions' => [
					'action' => [ 'key', 'both' ],
				],
			],
			'unique'         => [
				'label' => $this->trans( 'Only keep unique values?' ),
				'help'  => $this->trans( 'By default all values are kept.' ),
				'type'  => 'checkbox',
			],
			'keep_empty'     => [
				'label'      => $this->trans( 'Keep empty values?' ),
				'help'       => $this->trans( 'By default all empty column values will not be merged.' ),
				'type'       => 'checkbox',
				'conditions' => [
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

		$key = $config['key'];
		// @todo Support loop structure.

		$action = $config['action'] ?? 'value';

		$values = $data->get( $key );

		if ( 'both' === $action || 'key' === $action ) {
			switch ( $config['key_method'] ?? '' ) {
				case 'indexed':
					$values = [];

					$indexed = $config['index_key'] ?? '{*key*}_{*index*}';
					$indexed = str_replace( '{*key*}', $key, $indexed );

					$start = (int) ( $config['index_start'] ?? 0 );
					for (
						$i = $start;
							$index_key = str_replace( '{*index*}', $i, $indexed ),
							$value = $data->get( $index_key ),
							null !== $value;
						$i ++
					) {
						$values[ $i ] = $value;
						if ( ! empty( $config['remove'] ) ) {
							$data->unset( $index_key );
						}
					}
				break;
				case 'columns':
					if ( empty( $config['columns'] ) ) {
						$context->addError( $this->trans( 'No columns defined' ) );

						return $data;
					}

					$values = [];
					foreach ( array_column( $config['columns'], 'key' ) as $i => $column ) {
						$values[ $i ] = $data->get( $column );

						if ( ! empty( $config['remove'] ) ) {
							$data->unset( $column );
						}
					}
				break;
				default:
					$context->addError( $this->trans( 'No key method selected' ) );
				break;
			}

			// Combine nested values if configured.
			$method = $config['merge_method'] ?? 'list';
			if ( 'list' !== $method ) {
				$values = $this->_combineCollection( $values, $method );
			}
		}

		if ( is_array( $values ) && ! empty( $config['unique'] ) ) {
			$values = array_unique( $values );
		}

		if ( 'value' === $action || 'both' === $action ) {
			$separator = match ( $config['separator'] ?? '' ) {
				'{*nl*}' => "\n",
				'{*tab*}' => "	",
				default => $config['separator'] ?? '',
			};

			if ( is_array( $values ) ) {
				if ( empty( $config['keep_empty'] ) ) {
					$values = array_filter( $values );
				}

				if ( ! empty( $config['value_template'] ) ) {
					$template = $config['value_template'];
					$i        = 0;
					foreach ( $values as $k => $v ) {
						$values[ $k ] = str_replace(
							[ '{*key*}', '{*index*}', '{*value*}', '{*nl*}', '{*tab*}' ],
							[ $k, $i, $v, "\n", "	" ],
							$template
						);
						$i ++;
					}
				}

				// @todo How to handle nested values.
				$values = implode( $separator, $values );
			}
		}

		$data->set( $values, $key );

		return $data;
	}

	protected function _combineCollection( $values, $method )
	{
		if ( ! is_iterable( $values ) ) {
			return $values;
		}

		$return = [];

		switch ( $method ) {
			case 'replace':
				foreach ( $values as $value ) {
					$return = array_replace( $return, (array) $value );
				}
			break;
			case 'append':
				foreach ( $values as $value ) {
					// We're only adding new values, not replacing existing values.
					foreach ( (array) $value as $k => $v ) {
						if ( is_string( $k ) ) {
							if ( isset( $return[ $k ] ) ) {
								continue;
							}
							$return[ $k ] = $v;
						} else {
							$return[] = $v;
						}
					}
				}
			break;
			default:
				foreach ( $values as $value ) {
					$return = array_merge( $return, (array) $value );
				}
			break;
		}

		if ( ! empty( $config['unique'] ) ) {
			$return = array_unique( $return );
		}

		return $return;
	}
}
