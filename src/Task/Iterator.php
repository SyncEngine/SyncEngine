<?php

namespace App\Task;

use App\Model\TaskModel;
use App\Service\FlowService;
use App\Service\StepService;

class Iterator extends TaskModel
{
	public function __construct()
	{
		$this->type = 'iterator';
		$this->name = 'Iterator';
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
				'type' => 'select',
				'choices' => [
					'flow' => 'Flow',
					'step' => 'Step',
				]
			],
			'flow' => [
				'type' => 'select',
				'choices' => $flows,
				'conditionals' => [
					'action' => 'flow',
				]
			],
			'step' => [
				'type' => 'select',
				'choices' => $steps,
				'conditionals' => [
					'action' => 'step',
				]
			],
		];
	}

	public function execute( array $config, array $data ): array
	{
		$context = $data;

		$key = $config['key'] ?? '';

		if ( $key ) {
			$data = $data[ $config['key'] ] ?? [];
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
			break;
		}

		if ( $service && $action ) {
			foreach ( $data as $index => $value ) {
				$data[ $index ] = $service->execute( $action, $value, $context );
			}
		}

		if ( $key ) {
			$context[ $key ] = $data;
			$data = $context;
		}

		return $data;
	}
}
