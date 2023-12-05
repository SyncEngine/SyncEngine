<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Model\Trait\Conditionals;
use SyncEngine\Service\ExecutionContext;

class Choose extends TaskModel
{
	use Conditionals;

	public function __construct()
	{
		$this->type        = 'conditional';
		$this->name        = $this->trans( 'Choose' );
		$this->description = $this->trans( 'Choose between different options based on conditions' );

		parent::__construct();
	}

	public function getFields(): array
	{
		return [
			'options' => [
				'label'       => $this->trans( 'Options' ),
				'description' => $this->trans( 'Define the options and their conditionals. Once a conditional is valid it will run only that option.' ),
				'type'        => 'repeater',
				'actions'  => [
					'disable',
					'delete',
				],
				'fieldset'    => [
					'conditionals' => [
						'label'    => $this->trans( 'Conditionals' ),
						'type'     => 'conditionals',
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

	public function execute( array $config, ExecutionContext $context, array $data ): array
	{
		$options = $config['options'] ?? [];

		$actions = [];

		foreach ( $options as $option ) {
			if ( ! empty( $option['_disabled'] ) ) {
				continue;
			}

			if ( $this->validateConditionals( $option['conditionals'], $data, $context ) ) {
				$actions = $option['tasks'] ?: true;
				break;
			}
		}

		if ( ! $actions ) {
			$actions = $config['default'];
		}

		if ( is_array( $actions ) ) {
			$data = $context->getExecuteService()->executeTasks( $actions, $context, $data );
		}

		return $data;
	}
}
