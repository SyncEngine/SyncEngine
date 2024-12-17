<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\Data\ResourceData;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Task\Type\StructureTaskType;

class Merge extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = StructureTaskType::TYPE;
		$this->icon        = 'task-merge';
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
					'regex'   => $this->trans( 'Merge column keys using a regular expression' ),
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
			'regex_key'      => [
				'label'       => $this->trans( 'Regex to match keys with and merge' ),
				'type'        => 'text',
				'taggable'    => true,
				'conditions'  => [
					'action'     => [ 'key', 'both' ],
					'key_method' => 'regex',
				],
			],
			'index_key'      => [
				'label'       => $this->trans( 'Indexed key to search for and merge' ),
				'type'        => 'text',
				'help'        => [
					$this->trans( 'The template for the indexed keys' ),
					$this->trans( 'Nested keys are not supported' )
				],
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
				'default'    => '',
				'choices'    => [
					''        => $this->trans( 'Each column value will be appended' ),
					// array_merge
					'merge'   => $this->trans( 'Latter column values merge into the former' ),
					// array_replace
					'replace' => $this->trans( 'Latter column values replaces the former' ),
					// only add list items (numeric keys) or add items that do not yet exist.
					'insert'  => $this->trans( 'Latter column values insert into the former' ),
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
					' '       => $this->trans( 'Space' ),
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
				'description' => $this->trans( 'Wildcards: {wildcards}', [ 'wildcards' => '{*key*} {*index*} {*value*} {*nl*} {*tab*} {{ value.COLUMN_NAME }}' ] ),
				'taggable'    => true,
				'conditions'  => [
					'action' => [ 'value', 'both' ],
				],
			],
			'remove'         => [
				'label'      => $this->trans( 'Remove original column key(s)?' ),
				'type'       => 'switch',
				'conditions' => [
					'action' => [ 'key', 'both' ],
				],
			],
			'unique'         => [
				'label' => $this->trans( 'Only keep unique values?' ),
				'help'  => $this->trans( 'By default all values are kept.' ),
				'type'  => 'switch',
			],
			'preserve_keys'     => [
				'label'      => $this->trans( 'Preserve column keys?' ),
				'type'       => 'switch',
				'conditions' => [
					'action' => [ 'key' ],
				],
			],
			'keep_empty'     => [
				'label'      => $this->trans( 'Keep empty values?' ),
				'help'       => $this->trans( 'By default all empty column values will not be merged.' ),
				'type'       => 'switch',
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

		$values = $data->get( $key, [] );

		if ( 'both' === $action || 'key' === $action ) {
			switch ( $config['key_method'] ?? '' ) {
				case 'regex':
					$values = $data->filter(
						function( $_key ) use ( $config ) {
							return preg_match( $config['regex_key'], $_key );
						},
						ARRAY_FILTER_USE_KEY
					);
					if ( ! empty( $config['remove'] ) ) {
						foreach ( $values as $_key => $value ) {
							$data->unset( $_key );
						}
					}
				break;
				case 'indexed':
					$values = [];

					$key_template = $config['index_key'] ?? '{*key*}_{*index*}';
					$indexed      = str_replace( '{*key*}', $key, $key_template );

					$start = (int) ( $config['index_start'] ?? 0 );
					for (
						$i = $start;
							$index_key = str_replace( '{*index*}', $i, $indexed ),
							$value = $data->get( $index_key ),
							null !== $value;
						$i ++
					) {
						$values[ $index_key ] = $value;
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
					foreach ( array_column( $config['columns'], 'key' ) as $column ) {
						$values[ $column ] = $data->get( $column );

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
			$method = $config['merge_method'] ?? null;
			if ( $method ) {
				$values = $this->_combineCollection( $values, $method );
			}

			if ( empty( $config['preserve_keys'] ) ) {
				$values = ResourceData::values( $values );
			}
		}

		if ( is_scalar( $values ) ) {
			if ( ! empty( $values ) ) {
				// @todo Error?
				$context->addLog( $this->trans( 'Cannot merge scalar value.' ), [ 'value' => $values ] );
			}

			return $data;
		}

		$values = ResourceData::create( $values );

		if ( ! empty( $config['unique'] ) ) {
			$isList = $values->isList();
			$values = $values->unique();
			if ( $isList ) {
				// Reindex.
				$values = $values->list();
			}
		}

		if ( 'value' === $action || 'both' === $action ) {
			$separator = match ( $config['separator'] ?? '' ) {
				'{*nl*}' => "\n",
				'{*tab*}' => "	",
				default => $config['separator'] ?? '',
			};

			if ( ! empty( $config['value_template'] ) ) {
				$value_template = $config['value_template'];

				$i = 0;
				foreach ( $values as $k => $v ) {
					$template = $context->parseTag( $value_template, [ 'value' => $v ] ) ?? '';
					if ( ! is_scalar( $v ) && ! $v instanceof \Stringable ) {
						$v = '*value*';
					}
					$values[ $k ] = str_replace(
						[ '{*key*}', '{*index*}', '{*value*}', '{*nl*}', '{*tab*}' ],
						[ $k, $i, $v, "\n", "	" ],
						$template
					);
					$i ++;
				}
			}

			if ( empty( $config['keep_empty'] ) ) {
				$values = $values->filter();
			}

			$values = implode( $separator, $values->getArrayCopy() );
		}

		$data->set( ResourceData::data( $values ), $key );

		return $data;
	}

	protected function _combineCollection( $values, $method )
	{
		if ( ! is_iterable( $values ) ) {
			return $values;
		}

		$return = ResourceData::create();

		$method = match ( $method ) {
			'merge', 'replace', 'insert' => $method,
			default => null,
		};

		if ( ! $method ) {
			return $values;
		}

		foreach ( $values as $value ) {
			$return->$method( (array) $return->normalize( $value ?? [] ) );
		}

		return $return;
	}

	public function getTags(): array
	{
		return [
			'value' => '_input',
		];
	}
}
