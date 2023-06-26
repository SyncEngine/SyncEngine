<?php

namespace App\Task;

use App\Component\ExecutionContext;
use App\Model\TaskModel;
use App\Service\FlowService;
use App\Service\StepService;

class Loop extends TaskModel
{
	public function __construct()
	{
		$this->type = 'loop';
		$this->name = 'Loop';
		$this->description = 'Iterate over a set of rows';

		parent::__construct();
	}

	public function getFields(): array {

		$flows = [];
		foreach ( FlowService::getFlows() as $flow ) {
			$flows[ $flow->getId() ] = $flow->getName();
		}

		$steps = [];
		foreach ( StepService::getSteps() as $step ) {
			$steps[ $step->getId() ] = $step->getName();
		}

		return [
			'key' => [
				'type' => 'text',
				'label' => 'Key / Column',
				'description' => 'Leave empty for root iteration',
			],
			'action' => [
				'label' => 'Action',
				'type' => 'select',
				'choices' => [
					'flow' => 'Flow',
					'step' => 'Step',
				],
			],
			'flow' => [
				'label' => 'Flow',
				'type' => 'entity',
				'entity' => 'flow',
				'actions' => [ 'edit', 'create' ],
				'choices' => $flows,
				'conditionals' => [
					'action' => 'flow',
				],
			],
			'step' => [
				'label' => 'Step',
				'type' => 'entity',
				'entity' => 'step',
				'actions' => [ 'edit', 'create' ],
				'choices' => $steps,
				'conditionals' => [
					'action' => 'step',
				],
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
	{
		$loop = $data;
		$key  = $config['key'] ?? '';

		if ( $key ) {
			$loop = $loop[ $config['key'] ] ?? [];
		}

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
			foreach ( $loop as $index => $value ) {
				$loop[ $index ] = $service->execute( $action, $context, $value );
			}
			$context->ascend();
		}

		if ( $key ) {
			$data[ $key ] = $loop;
		} else {
			$data = $loop;
		}

		return $data;
	}
}
