<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ConditionsValidator;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Structure\Data\ConfigData;
use SyncEngine\Task\Type\ConditionTaskType;

class Choose extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = ConditionTaskType::TYPE;
		$this->icon        = 'task-choose';
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
						'source'    => [
							'customizable' => true,
							'choices' => [
								'' => 'Data',
								'{{ cache }}' => 'Cache',
								'{{ variables }}' => 'Variables',
							],
						],
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

	public function execute( ConfigData $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		$options = $config['options'] ?? [];

		$actions = [];

		$selected = null;
		$info = [];

		$validator = new ConditionsValidator();

		foreach ( $options as $index => $option ) {
			if ( ! empty( $option['_disabled'] ) ) {
				continue;
			}

			if ( $validator->validate( $option['conditions'], $data ) ) {
				$actions = $option['tasks'] ?: true;

				$selected = $this->trans(
					'Run option {name}',
					[ 'name' => $index . ( ! empty( $option['_label'] ) ? ': ' . $option['_label'] : '' ) ]
				);
				$info['conditions'] = $option['conditions'] ?? [];
				$info['data'] = $data;
				break;
			}
		}

		if ( ! $actions ) {
			$actions  = $config['default'] ?? null;
			$selected = $this->trans( 'Run default' );
		}

		$context->getTrace()?->enterTrace( $selected );

		// @todo Opt-in for debugging conditionals?
		//$context->addLog( 'Option info', $info );

		if ( is_array( $actions ) ) {
			$data = $context->getExecuteService()->executeTasks( $actions, $context, $data );
		}

		$context->getTrace()?->leaveTrace( $selected );

		return $data;
	}
}
