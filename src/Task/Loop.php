<?php

namespace App\Task;

use App\Model\FlowModel;
use App\Model\StepModel;
use App\Model\TaskModel;
use App\Service\ExecutionContext;
use App\Service\ResourceData;

class Loop extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'utility';
		$this->name        = 'Loop';
		$this->description = 'Iterate over a set of rows';

		parent::__construct();
	}

	public function getFields(): array
	{
		return [
			'key'    => [
				'type'        => 'text',
				'label'       => 'Key / Column',
				'description' => 'Leave empty for root iteration',
				'help'        => 'Nested keys are supported: key[nested_key]',
				'taggable'    => true,
			],
			'action' => [
				'label'   => 'Action',
				'type'    => 'select',
				'choices' => [
					'flow'  => 'Flow',
					'step'  => 'Step',
					'tasks' => 'Tasks',
				],
			],
			'flow'   => [
				'label'        => 'Flow',
				'type'         => 'entity',
				'entity'       => 'flow',
				'actions'      => [ 'edit', 'create' ],
				'conditionals' => [
					'action' => 'flow',
				],
			],
			'step'   => [
				'label'        => 'Step',
				'type'         => 'entity',
				'entity'       => 'step',
				'actions'      => [ 'edit', 'create' ],
				'conditionals' => [
					'action' => 'step',
				],
			],
			'tasks'  => [
				'label'        => 'Tasks',
				'type'         => 'tasks',
				'conditionals' => [
					'action' => 'tasks',
				],
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
	{
		$loop = new ResourceData( $data );
		$key  = $config['key'] ?? '';

		if ( $key ) {
			$loop = $loop->get( $key, [] );
		}

		switch ( $config['action'] ?? '' ) {
			case 'flow':
				$method = 'executeFlow';
				$action = FlowModel::get( $config['flow'] );
			break;
			case 'step':
				$method = 'executeStep';
				$action = StepModel::get( $config['step'] );
			break;
			case 'tasks':
				$method = 'executeTasks';
				$action = $config['tasks'];
			break;
			default:
				$context->addError( 'Invalid action' );

				return $data;
		}

		$service = $context->getExecuteService();

		if ( $service && $action ) {
			$context->next();
			foreach ( $loop as $index => $value ) {
				$loop[ $index ] = $service->$method( $action, $context, $value );
			}
			$context->previous();
		}

		if ( $key ) {
			$data[ $key ] = $loop->get();
		} else {
			$data = $loop->get();
		}

		return $data;
	}
}
