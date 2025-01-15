<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\Data\ResourceData;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
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
			'method'     => [
				'label'    => $this->trans( 'Method' ),
				'type'     => 'select',
				'default'  => '',
				'required' => true,
				'choices'  => [
					'key'    => $this->trans( 'Sort by key' ),
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

	public function execute( array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
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

		switch ( $method ) {
			case 'column':

				if ( empty( $config['sort_by'] ) ) {
					$context->addError( $this->trans( 'No sort column configured' ) );

					return $data;
				}

				$column   = $config['sort_by'];
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

				if ( $data->isList() ) {
					$data->usort( $sortFunction );
				} else {
					$data->uasort( $sortFunction );
				}

			break;

			case 'key':
				$reverse = str_contains( $order, 'desc' );

				if ( $reverse ) {
					$data->uksort( $callback );
				} else {
					// I'm assuming using core methods is faster.
					$natural = str_starts_with( $order, 'n' );
					if ( $natural ) {
						$data->ksort( SORT_NATURAL );
					} else {
						$data->ksort();
					}
				}
			break;
		}

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
		return strnatcmp( $a, $b );
	}

	public function sort_ndesc( $a, $b ): int
	{
		return strnatcmp( $b, $a );
	}
}
