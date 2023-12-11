<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\ResourceData;

class Set extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'modifier';
		$this->name        =  $this->trans( 'Set' );
		$this->description =  $this->trans( 'Set your own values' );
	}

	public function getFields(): array
	{
		return [
			'override'    => [
				'label'   =>  $this->trans( 'Override existing' ),
				'type'    => 'checkbox',
			],
			'key'       => [
				'label'        =>  $this->trans( 'Data key' ),
				'description'  =>  $this->trans( 'The data key for the values that needs to be set' ),
				'type'         => 'text',
				'taggable'     => true,
			],
			'params'    => [
				'label'    => '',
				'type'     => 'columns',
				'taggable' => true,
				'columns'  => [
					'key'   => 'Key name',
					'value' => 'Value',
				],
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$key    = $config['key'] ?? null;
		$params = $config['params'];

		if ( $key ) {
			return $data->set( $this->_execute( $params, $context, $data->set( $key ) ), $key );
		}

		return $this->_execute( $params, $context, $data );
	}

	public function _execute( $params, ExecutionContext $context, ExecuteData $resource )
	{
		if ( ! is_iterable( $resource ) ) {
			$context->addError( $this->trans( 'Data key not iterable' ) );

			return $resource;
		}

		foreach ( $params as $map ) {
			if ( ! isset( $map['key'] ) ) {
				continue;
			}

			$value = $map['value'] ?? '';

			$resource->set( $value, $map['key'] );
		}

		return $resource;
	}
}
