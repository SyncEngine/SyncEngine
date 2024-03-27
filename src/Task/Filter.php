<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Model\Trait\Conditions;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\Tag\TagParser;
use SyncEngine\Task\Type\ConditionTaskType;

class Filter extends TaskModel
{
	use Conditions;

	public function __construct()
	{
		parent::__construct();

		$this->type        = ConditionTaskType::TYPE;
		$this->name        = $this->trans( 'Filter' );
		$this->description = $this->trans( 'Filter data based on conditions' );
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

		// @todo Opt-out of preserve keys?
		foreach ( $data->get() as $index => $row ) {

			$valid = $this->validateConditions(
				( new TagParser( [ 'row' => $row ] ) )->parseTagArray( $conditions ),
				$row
			);

			$valid = ( $keepValid ) ? $valid : ! $valid;

			if ( ! $valid ) {
				unset( $data[ $index ] );
			}
		}

		return $data;
	}

	public function getTags(): array
	{
		return [
			'row',
		];
	}
}
