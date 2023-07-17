<?php

namespace App\Task;

use App\Component\ExecutionContext;
use App\Model\AutomationModel;
use App\Model\FlowModel;
use App\Model\StepModel;
use App\Model\TaskModel;
use App\Service\AutomationService;
use App\Service\FlowService;
use App\Service\StepService;

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
		$automations = [];
		foreach ( AutomationModel::getAll() as $automation ) {
			$automations[ $automation->getId() ] = $automation->getName();
		}

		$flows = [];
		foreach ( FlowModel::getAll() as $flow ) {
			$flows[ $flow->getId() ] = $flow->getName();
		}

		$steps = [];
		foreach ( StepModel::getAll() as $step ) {
			$steps[ $step->getId() ] = $step->getName();
		}

		return [
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
				],
			],
			'automation'    => [
				'label'        => 'Automation',
				'type'         => 'entity',
				'entity'       => 'automation',
				'actions'      => [ 'edit', 'create' ],
				'choices'      => $automations,
				'conditionals' => [
					'action' => 'automation',
				],
			],
			'flow'          => [
				'label'        => 'Flow',
				'type'         => 'entity',
				'entity'       => 'flow',
				'actions'      => [ 'edit', 'create' ],
				'choices'      => $flows,
				'conditionals' => [
					'action' => 'flow',
				],
			],
			'step'          => [
				'label'        => 'Step',
				'type'         => 'entity',
				'entity'       => 'step',
				'actions'      => [ 'edit', 'create' ],
				'choices'      => $steps,
				'conditionals' => [
					'action' => 'step',
				],
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
	{
		switch ( $config['action'] ?? '' ) {
			case 'automation':
				$service = new AutomationService();
				$model   = AutomationModel::get( $config['automation'] );
			break;
			case 'flow':
				$service = new FlowService();
				$model   = FlowModel::get( $config['flow'] );
			break;
			case 'step':
				$service = new StepService();
				$model   = StepModel::get( $config['step'] );
			break;
			default:
				// @todo error?
				return $data;
		}

		if ( $service && $model ) {
			$context->descend();

			$request = ( ! empty( $config['pass_data'] ) ) ? $data : [];

			$return = $service->execute( $model, $context, $request );

			if ( ! empty( $config['override_data'] ) ) {
				$data = $return;
			}

			$context->ascend();
		}

		return $data;
	}
}
