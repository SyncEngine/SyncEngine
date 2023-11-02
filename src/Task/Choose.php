<?php

namespace App\Task;

use App\Model\TaskModel;
use App\Model\Trait\Conditionals;
use App\Service\ExecutionContext;

class Choose extends TaskModel
{
	use Conditionals;

	public function __construct()
	{
		$this->type        = 'conditional';
		$this->name        = 'Choose';
		$this->description = 'Choose between different options based on conditions';

		parent::__construct();
	}

	public function getFields(): array
	{
		return [
			'options' => [
				'label'       => 'Options',
				'description' => 'Define the options and their conditionals. Once a conditional is valid it will run only that option.',
				'type'        => 'repeater',
				'actions'  => [
					'delete',
				],
				'fieldset'    => [
					'conditionals' => [
						'label'    => 'Conditionals',
						'type'     => 'conditionals',
						'required' => true,
						'taggable' => true,
					],
					'tasks'        => [
						'label'    => 'Tasks',
						'type'     => 'tasks',
						'required' => true,
					],
				],
			],
			'default' => [
				'label'       => 'Default tasks',
				'description' => 'These tasks will run if none of the option conditions are met.',
				'type'        => 'tasks',
				'collapsed'   => true,
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
	{
		$options = $config['options'] ?? [];

		$actions = [];

		foreach ( $options as $option ) {
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
