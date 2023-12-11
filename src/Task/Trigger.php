<?php

namespace SyncEngine\Task;

use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\StepModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;

class Trigger extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'utility';
		$this->name        = $this->trans( 'Trigger' );
		$this->description = $this->trans( 'Trigger something independently from the current flow' );
	}

	public function getFields(): array
	{
		return [
			'async'         => [
				'label'        => $this->trans( 'Run async?' ),
				'help'         => $this->trans( 'If the automation is using batches then this trigger will always run async.' ),
				'type'         => 'checkbox',
				'conditionals' => [
					'action'        => 'automation',
					'override_data' => [ 'operator' => 'empty' ],
				],
			],
			'pass_data'     => [
				'label' => $this->trans( 'Pass current data?' ),
				'type'  => 'checkbox',
			],
			'override_data' => [
				'label'        => $this->trans( 'Override current data?' ),
				'type'         => 'checkbox',
				'conditionals' => [
					'async' => [ 'operator' => 'empty' ],
				],
			],
			'action'        => [
				'label'   => $this->trans( 'Action' ),
				'type'    => 'select',
				'choices' => [
					'automation' => $this->trans( 'Automation' ),
					'flow'       => $this->trans( 'Flow' ),
					'step'       => $this->trans( 'Step' ),
					'tasks'      => $this->trans( 'Tasks' ),
				],
			],
			'automation'    => [
				'label'        => $this->trans( 'Automation' ),
				'type'         => 'entity',
				'entity'       => 'automation',
				'actions'      => [ 'edit', 'create' ],
				'conditionals' => [
					'action' => 'automation',
				],
			],
			'flow'          => [
				'label'        => $this->trans( 'Flow' ),
				'type'         => 'entity',
				'entity'       => 'flow',
				'actions'      => [ 'edit', 'create' ],
				'conditionals' => [
					'action' => 'flow',
				],
			],
			'step'          => [
				'label'        => $this->trans( 'Step' ),
				'type'         => 'entity',
				'entity'       => 'step',
				'actions'      => [ 'edit', 'create' ],
				'conditionals' => [
					'action' => 'step',
				],
			],
			'tasks'         => [
				'label'        => $this->trans( 'Tasks' ),
				'type'         => 'tasks',
				'conditionals' => [
					'action' => 'tasks',
				],
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$traverseAutomation = false;

		switch ( $config['action'] ?? '' ) {
			case 'automation':
				$method = 'execute';
				$action = AutomationModel::get( $config['automation'] );

				if ( ! empty( $config['async'] ) || $action->getIterator() ) {
					$context->getExecuteService()->schedule( $action );
					return $data;
				}

				$traverseAutomation = $action;
			break;
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

			if ( $traverseAutomation ) {
				$context = $context->descend( $traverseAutomation );
			} else {
				$context->next();
			}

			$request = ( ! empty( $config['pass_data'] ) ) ? $data : new ExecuteData();

			$return = $service->$method( $action, $context, $request );

			if ( ! empty( $config['override_data'] ) ) {
				$data = $return;
			}

			if ( $traverseAutomation ) {
				$context = $context->ascend();
			} else {
				$context->previous();
			}
		}

		return $data;
	}
}
