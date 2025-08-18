<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Structure\Data\ConfigData;
use SyncEngine\Structure\Data\ResourceData;
use SyncEngine\Task\Type\StructureTaskType;

class Sort extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = StructureTaskType::TYPE;
		$this->icon        = 'task-sort';
		$this->name        = $this->trans( 'Sort' );
		$this->description = $this->trans( 'Sort your data' );
	}

	public function getFields(): array
	{
		return [
			'key' => [
				'label'    => $this->trans( 'Key / Column name' ),
				'help'     => [
					$this->trans( 'The data column key name for the values that needs to be sorted' ),
					$this->trans( 'Nested keys are supported: {example}', [ 'example' => 'key.nested_key', ] ),
					$this->trans( 'Leave empty for root' ),
				],
				'type'     => 'text',
				'taggable' => true,
			],
			'method'     => [
				'label'    => $this->trans( 'Method' ),
				'type'     => 'select',
				'default'  => '',
				'required' => true,
				'choices'  => [
					'key'   => $this->trans( 'Sort by key / column name' ),
					'value' => $this->trans( 'Sort by value' ),
					'column' => $this->trans( 'Sort by column value' ),
				],
			],
			'sort_by'    => [
				'label'       => $this->trans( 'Sort by' ),
				'description' => $this->trans( 'Set column name/key to sort by value' ),
				'type'        => 'text',
				'default'     => '',
				'conditions'  => [ 'method' => 'column' ],
			],
			'sort_order' => [
				'label'    => $this->trans( 'Method' ),
				'type'     => 'select',
				'default'  => 'ASC',
				'required' => true,
				'choices'  => [
					'ASC'   => $this->trans( 'Ascending' ),
					'DESC'  => $this->trans( 'Descending' ),
					'NASC'  => $this->trans( 'Ascending natural order' ),
					'NDESC' => $this->trans( 'Descending natural order' ),
				],
			],
		];
	}

	public function execute( ConfigData $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['method'] ) ) {
			$context->addError( $this->trans( 'No sort method configured' ) );

			return $data;
		}

		$method = $config['method'];
		$order  = strtolower( $config['sort_order'] ?? 'asc' );

		$callback = [ $this, 'sort_' . $order ];

		if ( ! is_callable( $callback ) ) {
			$context->addError( $this->trans( 'Invalid sort order: {order}', [ 'order' => $order ] ) );

			return $data;
		}

		$key      = $config['key'] ?? null;
		$resource = $data->get( $key, [] );
		$raw      = is_array( $resource );
		$resource = ResourceData::create( $resource );

		switch ( $method ) {
			case 'column':
			case 'value':
				$sortFunction = $callback;

				if ( 'column' === $method ) {
					if ( empty( $config['sort_by'] ) ) {
						$context->addError( $this->trans( 'No sort column configured' ) );

						return $data;
					}

					$column   = $config['sort_by'] ?? null;
					$traverse = str_contains( $column, '.' );

					$sortFunction = function ( $a, $b ) use ( $column, $traverse, $callback ) {
						if ( $traverse ) {
							$av = ( new ResourceData( $a ) )->get( $column );
							$bv = ( new ResourceData( $b ) )->get( $column );
						} else {
							$av = $a[ $column ] ?? '';
							$bv = $b[ $column ] ?? '';
						}

						return $callback( $av, $bv );
					};
				}

				if ( $resource->isList() ) {
					$resource->usort( $sortFunction );
				} else {
					$resource->uasort( $sortFunction );
				}

			break;

			case 'key':
				$reverse = str_contains( $order, 'desc' );

				if ( $reverse ) {
					$resource->uksort( $callback );
				} else {
					// I'm assuming using core methods is faster.
					$natural = str_starts_with( $order, 'n' );
					if ( $natural ) {
						$resource->ksort( SORT_NATURAL );
					} else {
						$resource->ksort();
					}
				}
			break;
		}

		if ( $raw ) {
			$resource = $resource->normalize();
		}

		$data->set( $resource, $key );

		return $data;
	}

	public function sort_asc( $a, $b ): int
	{
		return $a <=> $b;
	}

	public function sort_desc( $a, $b ): int
	{
		return $b <=> $a;
	}

	public function sort_nasc( $a, $b ): int
	{
		return strnatcmp( (string) $a, (string) $b );
	}

	public function sort_ndesc( $a, $b ): int
	{
		return strnatcmp( (string) $b, (string) $a );
	}
}
