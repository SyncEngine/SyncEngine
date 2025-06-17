<?php

namespace SyncEngine\Task;

use SyncEngine\Model\FlowModel;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\Trace\TraceNode;
use SyncEngine\Structure\Data\ConfigData;
use SyncEngine\Structure\Data\ResourceData;
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
					'flow'    => $this->trans( 'Flow' ),
					'routine' => $this->trans( 'Routine' ),
					'tasks'   => $this->trans( 'Tasks' ),
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
			'routine'   => [
				'label'      => $this->trans( 'Routine' ),
				'type'       => 'entity',
				'entity'     => 'routine',
				'actions'    => [ 'edit', 'create' ],
				'conditions' => [
					'action' => 'routine',
				],
			],
			'tasks'  => [
				'label'       => [
					'text' => $this->trans( 'Tasks' ),
					'icon' => 'task',
				],
				'type'       => 'tasks',
				'conditions' => [
					'action' => 'tasks',
				],
			],
		];
	}

	public function execute( ConfigData $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		$key  = $config['key'] ?? null;
		$loop = $data->get( $key );

		if ( null === $loop ) {
			$context->addError( $this->trans( 'Data column not found: {key}', [ 'key' => $key ] ) );

			return $data;
		}

		if ( ! is_iterable( $loop ) ) {
			$context->addError( $this->trans( 'Data column not iterable: {key}', [ 'key' => $key ] ) );

			return $data;
		}

		switch ( $config['action'] ?? '' ) {
			case 'flow':
				$method = 'executeFlow';
				$action = FlowModel::get( $config['flow'] );
			break;
			case 'routine':
				$method = 'executeRoutine';
				$action = RoutineModel::get( $config['routine'] );
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

					$total = count( $batches );
					foreach ( $batches as $index => $batch ) {
						$context->setCurrent(
							[
								'index'     => $index,
								'data'      => $batch,
								'iteration' => [
									'current' => $index + 1,
									'index'   => $index,
								],
							],
							'loop'
						);

						// @todo Progress object?
						$context->getTrace()?->getCurrentTrace()->updateTrace( $config, function ( TraceNode $node ) use ( $index, $total ) {
							$node->set( [ 'total' => $total, 'current' => $index ], 'progress' );
						} );

						// Make sure to keep the array keys.
						//$batch = array_combine( array_keys( $batch ), $service->$method( $action, $context, $batch ) );
						$batch = $service->$method( $action, $context, ExecuteData::create( $batch ) );
						// Override keys with new values.
						$loop = array_replace( $loop, $batch->get() );
					}

					break;
				default:
					$count = 1;
					$total = count( $loop );

					foreach ( $loop as $index => $value ) {
						$context->setCurrent(
							[
								'index'     => $index,
								'data'      => ( $value instanceof ResourceData ) ? $value->normalize() : $value,
								'iteration' => [
									'current' => $count,
									'index'   => $count - 1,
								],
							],
							'loop'
						);

						// @todo Progress object?
						$context->getTrace()?->getCurrentTrace()->updateTrace( $config, function ( TraceNode $node ) use ( $count, $total ) {
							$node->set( [ 'total' => $total, 'current' => $count ], 'progress' );
						} );

						$loop[ $index ] = $service->$method( $action, $context, ExecuteData::create( $value ) );
						$count++;
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
			'index'     => '',
			'data'      => '',
			'iteration' => [
				'current' => 0,
				'index'   => 0,
			],
		];

		return $tags;
	}
}
