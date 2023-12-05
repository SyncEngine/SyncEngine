<?php

namespace SyncEngine\Task;

use SyncEngine\Model\FlowModel;
use SyncEngine\Model\StepModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\ResourceData;

class Loop extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'utility';
		$this->name        = $this->trans( 'Loop' );
		$this->description = $this->trans( 'Iterate over a set of rows' );

		parent::__construct();
	}

	public function getFields(): array
	{
		return [
			'key'    => [
				'type'        => 'text',
				'label'       => $this->trans( 'Key / Column' ),
				'description' => $this->trans( 'Leave empty for root iteration' ),
				'help'        => $this->trans( 'Nested keys are supported: key.nested_key' ),
				'taggable'    => true,
			],
			'action' => [
				'label'   => $this->trans( 'Action' ),
				'type'    => 'select',
				'choices' => [
					'flow'  => $this->trans( 'Flow' ),
					'step'  => $this->trans( 'Step' ),
					'tasks' => $this->trans( 'Tasks' ),
				],
			],
			'flow'   => [
				'label'        => $this->trans( 'Flow' ),
				'type'         => 'entity',
				'entity'       => 'flow',
				'actions'      => [ 'edit', 'create' ],
				'conditionals' => [
					'action' => 'flow',
				],
			],
			'step'   => [
				'label'        => $this->trans( 'Step' ),
				'type'         => 'entity',
				'entity'       => 'step',
				'actions'      => [ 'edit', 'create' ],
				'conditionals' => [
					'action' => 'step',
				],
			],
			'tasks'  => [
				'label'        => $this->trans( 'Tasks' ),
				'type'         => 'tasks',
				'conditionals' => [
					'action' => 'tasks',
				],
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, array $data ): array
	{
		$loop = new ResourceData( $data );
		$key  = $config['key'] ?? '';

		if ( $key ) {
			$loop = $loop->get( $key, [] );
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
			foreach ( $loop as $index => $value ) {
				$loop[ $index ] = $service->$method( $action, $context, $value );
			}
			$context->previous();
		}

		if ( $key ) {
			$resource = new ResourceData( $data );
			$resource[ $key ] = $loop;
			$data = $resource->get();
		} else {
			$data = $loop->get();
		}

		return $data;
	}
}
