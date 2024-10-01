<?php

namespace SyncEngine\Task;

use SyncEngine\Model\FlowModel;
use SyncEngine\Model\StepModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Task\Type\UtilityTaskType;

class Loop extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = UtilityTaskType::TYPE;
		$this->icon        = 'task-loop';
		$this->name        = $this->trans( 'Loop' );
		$this->description = $this->trans( 'Iterate over a set of rows' );
	}

	public function getFields(): array
	{
		return [
			'key'    => [
				'type'        => 'text',
				'label'       => $this->trans( 'Key / Column name' ),
				'help'        => [
					$this->trans( 'Nested keys are supported: key.nested_key' ),
					$this->trans( 'Leave empty for root iteration' ),
				],
				'taggable'    => true,
			],
			'method' => [
				'label'    => $this->trans( 'Loop method' ),
				'type'     => 'select',
				'default'  => '',
				'choices'  => [
					''      => $this->trans( 'Row' ),
					'batch' => $this->trans( 'Batches' ),
				],
			],
			'batch'        => [
				'label'      => $this->trans( 'Batch size' ),
				'help'       => $this->trans( 'Set the number of records to fetch/run at once.' ),
				'type'       => 'number',
				'required'   => true,
				'conditions' => [
					'method' => 'batch',
				],
			],
			'action' => [
				'label'    => $this->trans( 'Action' ),
				'type'     => 'select',
				'required' => true,
				'choices'  => [
					'flow'  => $this->trans( 'Flow' ),
					'step'  => $this->trans( 'Step' ),
					'tasks' => $this->trans( 'Tasks' ),
				],
			],
			'flow'   => [
				'label'      => $this->trans( 'Flow' ),
				'type'       => 'entity',
				'entity'     => 'flow',
				'actions'    => [ 'edit', 'create' ],
				'conditions' => [
					'action' => 'flow',
				],
			],
			'step'   => [
				'label'      => $this->trans( 'Step' ),
				'type'       => 'entity',
				'entity'     => 'step',
				'actions'    => [ 'edit', 'create' ],
				'conditions' => [
					'action' => 'step',
				],
			],
			'tasks'  => [
				'label'      => $this->trans( 'Tasks' ),
				'type'       => 'tasks',
				'conditions' => [
					'action' => 'tasks',
				],
			],
		];
	}

	public function execute( array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		$key  = $config['key'] ?? null;
		$loop = $data->get( $key );

		if ( null === $loop ) {
			$context->addError( $this->trans( 'Data key not found' ) );

			return $data;
		}

		if ( ! is_iterable( $loop ) ) {
			$context->addError( $this->trans( 'Data key not iterable' ) );

			return $data;
		}

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
				$context->addError( $this->trans( 'Invalid action' ) );

				return $data;
		}

		$service = $context->getExecuteService();

		if ( $service && $action ) {
			$context->next();

			switch ( $config['method'] ?? '' ) {
				case 'batch':
					if ( empty( $config['batch'] ) ) {
						$context->addError( $this->trans( 'No batch size configured' ) );

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
						$context->setCurrent( [ 'index' => $index, 'data' => $value ], 'loop' );
						$loop[ $index ] = $service->$method( $action, $context, new ExecuteData( $value ) );
					}
					break;
			}

			$context->setCurrent( null, 'loop' );
			$context->previous();
		}

		if ( $key ) {
			$data->set( $loop, $key );
		} else {
			$data = new ExecuteData( $loop );
		}

		return $data;
	}

	public function getTags(): array
	{
		$tags = parent::getTags();

		$tags['context']['loop'] = [
			'index' => '',
			'data'  => '',
		];

		return $tags;
	}
}
