<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Model\Trait\Conditions;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;

class Filter extends TaskModel
{
	use Conditions;

	public function __construct()
	{
		parent::__construct();

		$this->type        = 'condition';
		$this->name        = $this->trans( 'Filter' );
		$this->description = $this->trans( 'Remove elements based on conditions' );
	}

	public function getFields(): array
	{
		return [
			'key'    => [
				'type'        => 'text',
				'label'       => $this->trans( 'Key / Column' ),
				'description' => $this->trans( 'Leave empty for root iteration' ),
				'help'        => $this->trans( 'Nested keys are supported: key.nested_key' ),
				'taggable'    => true,
			],
			'method'    => [
				'label'    => $this->trans( 'Filter method' ),
				'type'     => 'select',
				'default'  => 'valid',
				'required' => true,
				'choices'  => [
					'valid'   => $this->trans( 'Keep valid rows' ),
					'invalid' => $this->trans( 'Keep invalid rows' ),
				],
			],
			'conditions' => [
				'label'    => $this->trans( 'Conditions' ),
				'type'     => 'conditions',
				'required' => true,
				'taggable' => true,
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['conditions'] ) ) {
			$context->addError( $this->trans( 'No conditions configured' ) );
		}

		$conditions = $config['conditions'];
		$keepValid  = 'invalid' !== $config['method'];

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
