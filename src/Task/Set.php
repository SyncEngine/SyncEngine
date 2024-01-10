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
		$this->name        = $this->trans( 'Set' );
		$this->description = $this->trans( 'Set your own values' );
	}

	public function getFields(): array
	{
		return [
			/*'override' => [
				'label' => $this->trans( 'Override existing' ),
				'type'  => 'checkbox',
			],*/
			'key' => [
				'label'       => $this->trans( 'Data key' ),
				'description' => $this->trans( 'The data key for the values that needs to be set' ),
				'type'        => 'text',
				'taggable'    => true,
			],
			'params'   => [
				'label'    => '',
				'type'     => 'grid',
				'taggable' => true,
				'columns'  => [
					'key'    => 'Key name',
					'value'  => [
						'label'        => 'Value',
						'customizable' => true,
						'choices'      => [
							''          => 'Unchanged',
							'{%unset%}' => 'Unset',
						],
					],
					'column' => [
						'label'        => 'Column Type',
						'customizable' => false,
						'choices'      => [
							''        => 'Unchanged',
							'string'  => 'Text',
							'number'  => 'Number',
							'boolean' => 'Boolean',
						],
					],
				],
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$key    = $config['key'] ?? null;
		$params = $config['params'];

		if ( $key ) {
			return $data->set( $this->_execute( $params, $context, $data->get( $key ) ?? [] ), $key );
		}

		return $this->_execute( $params, $context, $data );
	}

	public function _execute( iterable $params, ExecutionContext $context, mixed $resource ): mixed
	{
		if ( ! is_iterable( $resource ) ) {
			$context->addError( $this->trans( 'Data key not iterable' ) );

			return $resource;
		}

		foreach ( $params as $row ) {
			if ( ! isset( $row['key'] ) ) {
				continue;
			}

			$value  = $row['value'] ?? '';
			$column = $row['column'] ?? '';

			if ( '{%unset%}' === $value ) {
				unset( $resource[ $row['key'] ] );
				continue;
			}

			$value = $value ?: $resource[ $row['key'] ];

			if ( $column ) {
				switch ( $column ) {
					case 'string':
						$value = (string) $value;
					break;
					case 'number':
						$value = (float) $value;
					break;
					case 'boolean':
						$value = (bool) $value;
					break;
				}
			}

			$resource[ $row['key'] ] = $value;
		}

		return $resource;
	}
}
