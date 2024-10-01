<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Model\Trait\Conditions;
use SyncEngine\Service\Data\ResourceData;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\Tag\TagParser;
use SyncEngine\Task\Type\ConditionTaskType;

class Filter extends TaskModel
{
	use Conditions;

	public function __construct()
	{
		parent::__construct();

		$this->type        = ConditionTaskType::TYPE;
		$this->icon        = 'task-filter';
		$this->name        = $this->trans( 'Filter' );
		$this->description = $this->trans( 'Filter data based on conditions' );
	}

	public function getFields(): array
	{
		return [
			'key'        => [
				'type'        => 'text',
				'label'       => $this->trans( 'Key / Column name' ),
				'help'        => [
					$this->trans( 'Nested keys are supported: key.nested_key' ),
					$this->trans( 'Leave empty for root iteration' ),
				],
				'taggable'    => true,
			],
			'method'     => [
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

	public function execute( array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['conditions'] ) ) {
			$context->addError( $this->trans( 'No conditions configured' ) );
		}

		$key  = $config['key'] ?? null;
		$rows = $data->get( $key );

		if ( $rows instanceof ResourceData ) {
			$rows = $rows->get();
		}

		if ( null === $rows ) {
			$context->addError( $this->trans( 'Data key not found' ) );

			return $data;
		}

		if ( ! is_iterable( $rows ) ) {
			$context->addLog( $this->trans( 'Data not iterable' ) );

			return $data;
		}

		$conditions = $config['conditions'];
		foreach ( $conditions as &$condition ) {
			$condition['source'] = ( $condition['source'] ?? '{{ row }}' ) ?: '{{ row }}';
		}

		$keepValid  = 'invalid' !== $config['method'];
		$isList     = array_is_list( $rows );

		// @todo Opt-out of preserve keys?
		foreach ( $rows as $index => $row ) {

			$valid = $this->validateConditions(
				( new TagParser( [ 'row' => $row ] ) )->parseArray( $conditions ),
				$row
			);

			$valid = ( $keepValid ) ? $valid : ! $valid;

			if ( ! $valid ) {
				unset( $rows[ $index ] );
			}
		}

		$data->set( $isList ? array_values( $rows ) : $rows, $key );

		return $data;
	}

	public function getTags(): array
	{
		return [
			'row' => '_input',
		];
	}
}
