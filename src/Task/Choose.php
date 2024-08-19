<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Model\Trait\Conditions;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Task\Type\ConditionTaskType;

class Choose extends TaskModel
{
	use Conditions;

	public function __construct()
	{
		parent::__construct();

		$this->type        = ConditionTaskType::TYPE;
		$this->name        = $this->trans( 'Choose' );
		$this->description = $this->trans( 'Choose between different options based on conditions' );
	}

	public function getFields(): array
	{
		return [
			'options' => [
				'label'       => $this->trans( 'Options' ),
				'description' => $this->trans( 'Define the options and their conditions. Once a condition is valid it will run only that option.' ),
				'type'        => 'repeater',
				'actions'  => [
					'disable',
					'delete',
				],
				'fieldset'    => [
					'conditions' => [
						'label'    => $this->trans( 'Conditions'),
						'type'     => 'conditions',
						'required' => true,
						'taggable' => true,
					],
					'tasks'        => [
						'label'    => $this->trans( 'Tasks' ),
						'type'     => 'tasks',
						'required' => true,
					],
				],
			],
			'default' => [
				'label'       => $this->trans( 'Default tasks' ),
				'description' => $this->trans( 'These tasks will run if none of the option conditions are met.' ),
				'type'        => 'tasks',
				'collapsed'   => true,
			],
		];
	}

	public function execute( array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
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
