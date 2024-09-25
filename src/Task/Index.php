<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\Data\ResourceData;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\Tag\TagParser;
use SyncEngine\Task\Type\StructureTaskType;

class Index extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = StructureTaskType::TYPE;
		$this->name        = $this->trans( 'Index' );
		$this->description = $this->trans( 'Index your data' );
	}

	public function getFields(): array
	{
		return [
			'key'       => [
				'label'    => $this->trans( 'Key / Column name' ),
				'type'     => 'text', // @todo Column/Key selection field type.
				'help'     => [
					$this->trans( 'The data column name to index items for' ),
					$this->trans( 'Nested keys are supported: key.nested_key' ),
					$this->trans( 'Leave empty for root' ),
				],
				'taggable' => true,
			],
			'method'    => [
				'label'    => $this->trans( 'Method' ),
				'type'     => 'select',
				'default'  => '',
				'required' => true,
				'choices'  => [
					'list'  => $this->trans( 'Numeric list' ),
					'assoc' => $this->trans( 'Associative indexes' ),
				],
			],
			'index_key' => [
				'label'       => $this->trans( 'New index key' ),
				'type'        => 'text',
				'help'        => $this->trans( 'The template for the indexed keys' ),
				'description' => $this->trans(
					'Use column keys as tags: {tag} | Wildcards: {wildcards}',
					[ 'tag' => '{{ row.column }}', 'wildcards' => '{*key*}' ]
				),
				// @todo Convert this to Tags (Needs big refactor in Execute service.
				'default'     => '',
				'taggable'    => true,
				'conditions'  => [
					'method'  => [ 'assoc' ],
				],
			],
		];
	}

	public function execute( array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['method'] ) ) {
			$context->addError( $this->trans( 'No index method configured' ) );

			return $data;
		}

		$method = $config['method'];

		if ( 'assoc' === $method && empty( $config['index_key'] ) ) {
			$context->addError( $this->trans( 'No index key template configured' ) );

			return $data;
		}

		$key   = $config['key'] ?? null;
		$items = $data->get( $key );

		if ( ! is_iterable( $items ) ) {
			$context->addLog( $this->trans( 'Data not iterable' ) );

			return $data;
		}

		if ( 'list' === $method ) {
			$indexed = ResourceData::values( $items );
		} else {
			$indexed = [];
			foreach ( $items as $index => $value ) {
				$new_index = ( new TagParser( [ 'row' => $value ] ) )->parseString( $config['index_key'] );

				if ( is_string( $new_index ) || is_numeric( $new_index ) ) {
					if ( is_string( $new_index ) ) {
						$new_index = str_replace( '{*key*}', $index, $new_index );
					}

					$indexed[ $new_index ] = $value;
				}
			}
		}

		$data->set( $indexed, $key );

		return $data;
	}

	public function getTags(): array
	{
		return [
			'row' => '_input',
		];
	}
}
