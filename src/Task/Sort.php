<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\ResourceData;
use SyncEngine\Task\Type\StructureTaskType;

class Sort extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = StructureTaskType::TYPE;
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

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['method'] ) ) {
			$context->addError( $this->trans( 'No sort method configured' ) );

			return $data;
		}

		$method  = $config['method'];
		$order   = $config['sort_order'] ?? 'ASC';
		$reverse = str_contains( $order, 'DESC' );
		$natural = str_starts_with( $order, 'N' );

		switch ( $method ) {
			case 'column':

				if ( empty( $config['sort_by'] ) ) {
					$context->addError( $this->trans( 'No sort column configured' ) );

					return $data;
				}

				$column   = $config['sort_by'];
				$traverse = str_contains( $column, '.' );

				$data->uasort(
					function ( $a, $b ) use ( $column, $traverse, $reverse, $natural ) {
						if ( $traverse ) {
							$av = ( new ResourceData( $a ) )->get( $column );
							$bv = ( new ResourceData( $b ) )->get( $column );
						} else {
							$av = $a[ $column ] ?? '';
							$bv = $b[ $column ] ?? '';
						}

						if ( $natural ) {
							if ( $reverse ) {
								return strnatcmp( $bv, $av );
							}

							return strnatcmp( $av, $bv );
						}

						if ( $reverse ) {
							return $bv <=> $av;
						}

						return $av <=> $bv;
					}
				);
			break;

			case 'key':
				if ( $natural ) {
					$data->ksort( SORT_NATURAL );
				} else {
					$data->ksort();
				}
			break;
		}

		return $data;
	}
}
