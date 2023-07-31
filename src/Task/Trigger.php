<?php

namespace App\Task;

use App\Model\AutomationModel;
use App\Model\FlowModel;
use App\Model\StepModel;
use App\Model\TaskModel;
use App\Service\ExecutionContext;

class Trigger extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'utility';
		$this->name        = 'Trigger';
		$this->description = 'Trigger something independently from the current flow';

		parent::__construct();
	}

	public function getFields(): array
	{
		return [
			'async'         => [
				'label'        => 'Run async?',
				'help'         => 'If the automation is using batches then this trigger will always run async.',
				'type'         => 'checkbox',
				'conditionals' => [
					'action'        => 'automation',
					'override_data' => [ 'operator' => 'empty' ],
				],
			],
			'pass_data'     => [
				'label' => 'Pass current data?',
				'type'  => 'checkbox',
			],
			'override_data' => [
				'label'        => 'Override current data?',
				'type'         => 'checkbox',
				'conditionals' => [
					'async' => [ 'operator' => 'empty' ],
				],
			],
			'action'        => [
				'label'   => 'Action',
				'type'    => 'select',
				'choices' => [
					'automation' => 'Automation',
					'flow'       => 'Flow',
					'step'       => 'Step',
					'tasks'      => 'Tasks',
				],
			],
			'automation'    => [
				'label'        => 'Automation',
				'type'         => 'entity',
				'entity'       => 'automation',
				'actions'      => [ 'edit', 'create' ],
				'conditionals' => [
					'action' => 'automation',
				],
			],
			'flow'          => [
				'label'        => 'Flow',
				'type'         => 'entity',
				'entity'       => 'flow',
				'actions'      => [ 'edit', 'create' ],
				'conditionals' => [
					'action' => 'flow',
				],
			],
			'step'          => [
				'label'        => 'Step',
				'type'         => 'entity',
				'entity'       => 'step',
				'actions'      => [ 'edit', 'create' ],
				'conditionals' => [
					'action' => 'step',
				],
			],
			'tasks'         => [
				'label'        => 'Tasks',
				'type'         => 'tasks',
				'conditionals' => [
					'action' => 'tasks',
				],
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
	{
		switch ( $config['action'] ?? '' ) {
			case 'automation':
				$method = 'execute';
				$action = AutomationModel::get( $config['automation'] );

				if ( ! empty( $config['async'] ) || $action->getIterator() ) {
					$context->getExecuteService()->schedule( $action );
					return $data;
				}
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
				$context->addError( 'Invalid action' );
				return $data;
		}

		$service = $context->getExecuteService();

		if ( $service && $action ) {
			$context->descend();

			$request = ( ! empty( $config['pass_data'] ) ) ? $data : [];

			$return = $service->$method( $action, $context, $request );

			if ( ! empty( $config['override_data'] ) ) {
				$data = $return;
			}

			$context->ascend();
		}

		return $data;
	}
}
