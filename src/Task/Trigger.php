<?php

namespace SyncEngine\Task;

use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Structure\Data\ConfigData;
use SyncEngine\Structure\Data\ResourceData;
use SyncEngine\Task\Type\UtilityTaskType;

class Trigger extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = UtilityTaskType::TYPE;
		$this->icon        = 'task-trigger';
		$this->name        = $this->trans( 'Trigger' );
		$this->description = $this->trans( 'Trigger something independently from the current flow' );
	}

	public function getFields(): array
	{
		return [
			'async'         => [
				'label'      => $this->trans( 'Run async?' ),
				'help'       => $this->trans( 'If the automation is using batches then this trigger will always run async.' ),
				'type'       => 'switch',
				'conditions' => [
					'action'        => 'automation',
					'override_data' => [ 'operator' => 'empty' ],
				],
			],
			'pass_data'     => [
				'label' => $this->trans( 'Pass current data?' ),
				'type'  => 'switch',
			],
			'override_data' => [
				'label'      => $this->trans( 'Override current data?' ),
				'type'       => 'switch',
				'conditions' => [
					'async' => [ 'operator' => 'empty' ],
				],
			],
			'key'    => [
				'type'        => 'text',
				'label'       => $this->trans( 'Key / Column name' ),
				'help'        => [
					$this->trans( 'Nested keys are supported: key.nested_key' ),
					$this->trans( 'Leave empty to pass root data' ),
				],
				'taggable'    => true,
				'conditions'  => [ 'pass_data' => true ],
			],
			'variables' => [
				'label'       => [
					'text' => $this->trans( 'Variables' ),
					'icon' => 'variable',
				],
				'description' => $this->trans( 'Define static variables to be used for this trigger. Existing variables will also be included.' ),
				'type'        => 'params',
				'collapsed'   => true,
				'taggable'    => true,
			],
			'action'        => [
				'label'    => $this->trans( 'Action' ),
				'type'     => 'select',
				'required' => true,
				'choices'  => [
					'automation' => $this->trans( 'Automation' ),
					'flow'       => $this->trans( 'Flow' ),
					'routine'    => $this->trans( 'Routine' ),
					'tasks'      => $this->trans( 'Tasks' ),
				],
			],
			'automation'    => [
				'label'      => $this->trans( 'Automation' ),
				'type'       => 'entity',
				'entity'     => 'automation',
				'actions'    => [ 'edit', 'create' ],
				'conditions' => [
					'action' => 'automation',
				],
			],
			'flow'          => [
				'label'      => $this->trans( 'Flow' ),
				'type'       => 'entity',
				'entity'     => 'flow',
				'actions'    => [ 'edit', 'create' ],
				'conditions' => [
					'action' => 'flow',
				],
			],
			'routine'       => [
				'label'      => $this->trans( 'Routine' ),
				'type'       => 'entity',
				'entity'     => 'routine',
				'actions'    => [ 'edit', 'create' ],
				'conditions' => [
					'action' => 'routine',
				],
			],
			'tasks'         => [
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
		$traverseAutomation = false;

		switch ( $config['action'] ?? '' ) {
			case 'automation':
				$method = 'execute';
				$action = AutomationModel::get( $config['automation'] );

				if ( ! empty( $config['async'] ) || $action->hasIterator() ) {
					// @todo Request query and params?
					$context->getExecuteService()->schedule( $action, $context );
					return $data;
				}

				$traverseAutomation = $action;
			break;
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

			$key       = $config['key'] ?? null;
			$variables = $config['variables'] ?? [];

			if ( $traverseAutomation ) {
				$context = $context->descend( $traverseAutomation, $variables );
			} else {
				$context = $context->descend( null, $variables );
			}

			$request = new ExecuteData();
			if ( ! empty( $config['pass_data'] ) ) {
				$request->set( ExecuteData::create( $data->get( $key ) )->normalize() );
			}

			$return = $service->$method( $action, $context, $request );

			if ( ! empty( $config['override_data'] ) ) {
				$data->set( $return instanceof ResourceData ? $return->get() : $return, $key );
			}

			$context->ascend();
		}

		return $data;
	}
}
