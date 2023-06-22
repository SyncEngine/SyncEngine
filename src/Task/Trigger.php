<?php

namespace App\Task;

use App\Component\AutomationContext;
use App\Model\TaskModel;
use App\Service\AutomationService;
use App\Service\FlowService;
use App\Service\StepService;

class Trigger extends TaskModel
{
	public function __construct()
	{
		$this->type = 'trigger';
		$this->name = 'Trigger';
		$this->description = 'Trigger something independently from the current flow';

		parent::__construct();
	}

	public function getFields(): array
	{
		$flows = [];
		foreach ( FlowService::getFlows() as $flow ) {
			$flows[ $flow->getId() ] = $flow->getName();
		}

		$steps = [];
		foreach ( StepService::getSteps() as $step ) {
			$steps[ $step->getId() ] = $step->getName();
		}

		return [
			'action' => [
				'label' => 'Action',
				'type' => 'select',
				'choices' => [
					'flow'       => 'Flow',
					'step'       => 'Step',
				],
			],
			'flow' => [
				'label' => 'Flow',
				'type' => 'select',
				'choices' => $flows,
				'conditionals' => [
					'action' => 'flow',
				],
			],
			'step' => [
				'label' => 'Step',
				'type' => 'select',
				'choices' => $steps,
				'conditionals' => [
					'action' => 'step',
				],
			],
		];
	}

	function execute( array $config, AutomationContext $context, $data )
	{
		switch ( $config['action'] ?? '' ) {
			case 'flow':
				$service = new FlowService();
				$action = $service->getFlow( $config['flow'] );
			break;
			case 'step':
				$service = new StepService();
				$action = $service->getStep( $config['step'] );
			break;
			default:
				// @todo error?
				return $data;
		}

		if ( $service && $action ) {
			$context->descend();
			$service->execute( $action, $context, $data );
			$context->ascend();
		}

		return $data;
	}
}
