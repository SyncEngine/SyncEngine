<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\Data\ResourceData;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Task\Type\StructureTaskType;

class Extract extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = StructureTaskType::TYPE;
		$this->name        = $this->trans( 'Extract' );
		$this->description = $this->trans( 'Extract a column from each item in your data' );
	}

	public function getFields(): array
	{
		return [
			'key'       => [
				'label'    => $this->trans( 'Key / Column name' ),
				'type'     => 'text', // @todo Column/Key selection field type.
				'help'     => [
					$this->trans( 'The data column name to extract items in' ),
					$this->trans( 'Nested keys are supported: key.nested_key' ),
					$this->trans( 'Leave empty for root' ),
				],
				'taggable' => true,
			],
			'column_key' => [
				'label'       => $this->trans( 'The column to extract' ),
				'type'        => 'text',
				'help'        => [
					$this->trans( 'Nested keys are supported: key.nested_key' ),
					$this->trans( 'Leave empty for root' ),
				],
				'default'     => '',
				'taggable'    => true,
			],
		];
	}

	public function execute( array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		$column_key = $config['column_key'] ?? null;
		if ( empty( $config['column_key'] ) ) {
			$context->addError( $this->trans( 'No column key configured' ) );

			return $data;
		}

		$key   = $config['key'] ?? null;
		$items = $data->get( $key );

		if ( ! is_iterable( $items ) ) {
			$context->addLog( $this->trans( 'Data not iterable' ) );

			return $data;
		}

		$extracted = [];
		foreach ( $items as $index => $value ) {
			if ( ! is_iterable( $value ) ) {
				$context->addLog( $this->trans( 'Could not extract' ), [ 'config' => $config, 'data' => $value ] );

				// @todo define default value or other method to handle invalid items?
				$extracted[ $index ] = null;
			} else {
				$extracted[ $index ] = ResourceData::create( $value )->get( $column_key );
			}
		}

		$data->set( $extracted, $key );

		return $data;
	}
}
