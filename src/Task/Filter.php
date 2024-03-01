<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Model\Trait\Conditions;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Task\Type\ConditionTaskType;

class Filter extends TaskModel
{
	use Conditions;

	public function __construct()
	{
		parent::__construct();

		$this->type        = ConditionTaskType::TYPE;
		$this->name        = $this->trans( 'Filter',[],"task/filter" );
		$this->description = $this->trans( 'Filter data based on conditions',[],"task/filter" );
	}

	public function getFields(): array
	{
		return [
			'key'    => [
				'type'        => 'text',
				'label'       => $this->trans( 'Key / Column',[],"task/filter" ),
				'description' => $this->trans( 'Leave empty for root iteration',[],"task/filter" ),
				'help'        => $this->trans( 'Nested keys are supported: key.nested_key',[],"task/filter" ),
				'taggable'    => true,
			],
			'method'    => [
				'label'    => $this->trans( 'Filter method',[],"task/filter" ),
				'type'     => 'select',
				'default'  => 'valid',
				'required' => true,
				'choices'  => [
					'valid'   => $this->trans( 'Keep valid rows',[],"task/filter" ),
					'invalid' => $this->trans( 'Keep invalid rows',[],"task/filter" ),
				],
			],
			'conditions' => [
				'label'    => $this->trans( 'Conditions',[],"task/filter" ),
				'type'     => 'conditions',
				'required' => true,
				'taggable' => true,
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['conditions'] ) ) {
			$context->addError( $this->trans( 'No conditions configured',[],"task/filter" ) );
		}

		$conditions = $config['conditions'];
		$keepValid  = 'invalid' !== $config['method'];

		// @todo Opt-out of preserve keys?
		foreach ( $data->get() as $index => $row ) {
			$valid = $this->validateConditions( $conditions, $row );

			$valid = ( $keepValid ) ? $valid : ! $valid;

			if ( ! $valid ) {
				unset( $data[ $index ] );
			}
		}

		return $data;
	}
}
