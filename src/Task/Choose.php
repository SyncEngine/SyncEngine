<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Model\Trait\Conditions;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Task\Type\ConditionTaskType;

class Choose extends TaskModel
{
	use Conditions;

	public function __construct()
	{
		parent::__construct();

		$this->type        = ConditionTaskType::TYPE;
		$this->name        = $this->trans( 'Choose',[],"task/choose" );
		$this->description = $this->trans( 'Choose between different options based on conditions',[],"task/choose" );
	}

	public function getFields(): array
	{
		return [
			'options' => [
				'label'       => $this->trans( 'Options',[],"task/choose" ),
				'description' => $this->trans( 'Define the options and their conditions. Once a condition is valid it will run only that option.',[],"task/choose" ),
				'type'        => 'repeater',
				'actions'  => [
					'disable',
					'delete',
				],
				'fieldset'    => [
					'conditions' => [
						'label'    => $this->trans( 'Conditions',[],"task/choose"),
						'type'     => 'conditions',
						'required' => true,
						'taggable' => true,
					],
					'tasks'        => [
						'label'    => $this->trans( 'Tasks',[],"task/choose" ),
						'type'     => 'tasks',
						'required' => true,
					],
				],
			],
			'default' => [
				'label'       => $this->trans( 'Default tasks',[],"task/choose" ),
				'description' => $this->trans( 'These tasks will run if none of the option conditions are met.',[],"task/choose" ),
				'type'        => 'tasks',
				'collapsed'   => true,
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$options = $config['options'] ?? [];

		$actions = [];

		foreach ( $options as $option ) {
			if ( ! empty( $option['_disabled'] ) ) {
				continue;
			}

			if ( $this->validateConditions( $option['conditions'], $data ) ) {
				$actions = $option['tasks'] ?: true;
				break;
			}
		}

		if ( ! $actions ) {
			$actions = $config['default'] ?? null;
		}

		if ( is_array( $actions ) ) {
			$data = $context->getExecuteService()->executeTasks( $actions, $context, $data );
		}

		return $data;
	}
}
