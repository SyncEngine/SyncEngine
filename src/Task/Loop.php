<?php

namespace SyncEngine\Task;

use SyncEngine\Model\FlowModel;
use SyncEngine\Model\StepModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Task\Type\UtilityTaskType;

class Loop extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = UtilityTaskType::TYPE;
		$this->name        = $this->trans( 'Loop',[],"task/index" );
		$this->description = $this->trans( 'Iterate over a set of rows',[],"task/index" );
	}

	public function getFields(): array
	{
		return [
			'key'    => [
				'type'        => 'text',
				'label'       => $this->trans( 'Key / Column',[],"task/index" ),
				'description' => $this->trans( 'Leave empty for root iteration',[],"task/index" ),
				'help'        => $this->trans( 'Nested keys are supported: key.nested_key',[],"task/index" ),
				'taggable'    => true,
			],
			'method' => [
				'label'    => $this->trans( 'Loop method',[],"task/index" ),
				'type'     => 'select',
				'default'  => '',
				'choices'  => [
					''      => $this->trans( 'Row',[],"task/index" ),
					'batch' => $this->trans( 'Batches',[],"task/index" ),
				],
			],
			'batch'        => [
				'label'      => $this->trans( 'Batch size',[],"task/index" ),
				'help'       => $this->trans( 'Set the number of records to fetch/run at once.',[],"task/index" ),
				'type'       => 'number',
				'required'   => true,
				'conditions' => [
					'method' => 'batch',
				],
			],
			'action' => [
				'label'    => $this->trans( 'Action',[],"task/index" ),
				'type'     => 'select',
				'required' => true,
				'choices'  => [
					'flow'  => $this->trans( 'Flow',[],"task/index" ),
					'step'  => $this->trans( 'Step',[],"task/index" ),
					'tasks' => $this->trans( 'Tasks',[],"task/index" ),
				],
			],
			'flow'   => [
				'label'      => $this->trans( 'Flow',[],"task/index" ),
				'type'       => 'entity',
				'entity'     => 'flow',
				'actions'    => [ 'edit', 'create' ],
				'conditions' => [
					'action' => 'flow',
				],
			],
			'step'   => [
				'label'      => $this->trans( 'Step',[],"task/index" ),
				'type'       => 'entity',
				'entity'     => 'step',
				'actions'    => [ 'edit', 'create' ],
				'conditions' => [
					'action' => 'step',
				],
			],
			'tasks'  => [
				'label'      => $this->trans( 'Tasks',[],"task/index" ),
				'type'       => 'tasks',
				'conditions' => [
					'action' => 'tasks',
				],
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$key  = $config['key'] ?? '';
		$loop = $data->get( $key ?? null, [] );

		switch ( $config['action'] ?? '' ) {
			case 'flow':
				$method = 'executeFlow';
				$action = FlowModel::get( $config['flow'] );
			break;
			case 'step':
				$method = 'executeStep';
				$action = StepModel::get( $config['step'] );
			break;
			case 'tasks':
				$method = 'executeTasks';
				$action = $config['tasks'];
			break;
			default:
				$context->addError( $this->trans( 'Invalid action',[],"task/index" ) );

				return $data;
		}

		$service = $context->getExecuteService();

		if ( $service && $action ) {
			$context->next();

			switch ( $config['method'] ?? '' ) {
				case 'batch':
					if ( empty( $config['batch'] ) ) {
						$context->addError( $this->trans( 'No batch size configured',[],"task/index" ) );

						return $data;
					}

					$batches = array_chunk( $loop, (int) $config['batch'], true );

					foreach ( $batches as $batch ) {
						// Make sure to keep the array keys.
						//$batch = array_combine( array_keys( $batch ), $service->$method( $action, $context, $batch ) );
						$batch = $service->$method( $action, $context, new ExecuteData( $batch ) );
						// Override keys with new values.
						$loop = array_replace( $loop, $batch->get() );
					}

					break;
				default:
					foreach ( $loop as $index => $value ) {
						$loop[ $index ] = $service->$method( $action, $context, new ExecuteData( $value ) );
					}
					break;
			}

			$context->previous();
		}

		if ( $key ) {
			$data->set( $loop, $key );
		} else {
			$data = new ExecuteData( $loop );
		}

		return $data;
	}
}
