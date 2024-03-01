<?php

namespace SyncEngine\Task;

use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\StepModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Task\Type\UtilityTaskType;

class Trigger extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = UtilityTaskType::TYPE;
		$this->name        = $this->trans( 'Trigger',[],"task/trigger" );
		$this->description = $this->trans( 'Trigger something independently from the current flow',[],"task/trigger" );
	}

	public function getFields(): array
	{
		return [
			'async'         => [
				'label'      => $this->trans( 'Run async?',[],"task/trigger" ),
				'help'       => $this->trans( 'If the automation is using batches then this trigger will always run async.',[],"task/trigger" ),
				'type'       => 'checkbox',
				'conditions' => [
					'action'        => 'automation',
					'override_data' => [ 'operator' => 'empty' ],
				],
			],
			'pass_data'     => [
				'label' => $this->trans( 'Pass current data?',[],"task/trigger" ),
				'type'  => 'checkbox',
			],
			'override_data' => [
				'label'      => $this->trans( 'Override current data?',[],"task/trigger" ),
				'type'       => 'checkbox',
				'conditions' => [
					'async' => [ 'operator' => 'empty' ],
				],
			],
			'action'        => [
				'label'    => $this->trans( 'Action',[],"task/trigger" ),
				'type'     => 'select',
				'required' => true,
				'choices'  => [
					'automation' => $this->trans( 'Automation',[],"task/trigger" ),
					'flow'       => $this->trans( 'Flow',[],"task/trigger" ),
					'step'       => $this->trans( 'Step',[],"task/trigger" ),
					'tasks'      => $this->trans( 'Tasks',[],"task/trigger" ),
				],
			],
			'automation'    => [
				'label'      => $this->trans( 'Automation',[],"task/trigger" ),
				'type'       => 'entity',
				'entity'     => 'automation',
				'actions'    => [ 'edit', 'create' ],
				'conditions' => [
					'action' => 'automation',
				],
			],
			'flow'          => [
				'label'      => $this->trans( 'Flow',[],"task/trigger" ),
				'type'       => 'entity',
				'entity'     => 'flow',
				'actions'    => [ 'edit', 'create' ],
				'conditions' => [
					'action' => 'flow',
				],
			],
			'step'          => [
				'label'      => $this->trans( 'Step',[],"task/trigger" ),
				'type'       => 'entity',
				'entity'     => 'step',
				'actions'    => [ 'edit', 'create' ],
				'conditions' => [
					'action' => 'step',
				],
			],
			'tasks'         => [
				'label'      => $this->trans( 'Tasks',[],"task/trigger" ),
				'type'       => 'tasks',
				'conditions' => [
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

				if ( ! empty( $config['async'] ) || $action->hasIterator() ) {
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
				$context->addError( $this->trans( 'Invalid action',[],"task/trigger" ) );
				return $data;
		}

		$service = $context->getExecuteService();

		if ( $service && $action ) {

			if ( $traverseAutomation ) {
				$context = $context->descend( $traverseAutomation );
			} else {
				$context->next();
			}

			$request = new ExecuteData();
			if ( ! empty( $config['pass_data'] ) ) {
				$request->set( $data->get() );
			}

			$return = $service->$method( $action, $context, $request );

			if ( ! empty( $config['override_data'] ) ) {
				$data = ExecuteData::create( $return );
			}

			if ( $traverseAutomation ) {
				$context->ascend();
			} else {
				$context->previous();
			}
		}

		return $data;
	}
}
