<?php

namespace SyncEngine\Task;

use SyncEngine\Model\ColumnModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Task\Type\ModifierTaskType;

class Set extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = ModifierTaskType::TYPE;
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
					'key'    => $this->trans('Key name'),
					'value'  => [
						'label'        => $this->trans('Value'),
						'customizable' => true,
						'selectLabel'  => $this->trans('-- Unchanged --'),
						'help'         => $this->trans( 'Use {%value%} to insert the current value.' ),
						'choices'      => [
							'{%unset%}' => $this->trans('Unset'),
						],
					],
					'column' => [
						'label' => $this->trans('Column Type'),
						'type'  => 'column',
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

			if ( '{%unset%}' === $value ) {
				unset( $resource[ $row['key'] ] );
				continue;
			}

			if ( ! $value ) {
				$value = $resource[ $row['key'] ];
			} else {
				$value = str_replace( '{%value%}', $resource[ $row['key'] ], $value );
			}

			$columnConfig = $row['column'] ?? '';
			if ( $columnConfig ) {
				$value = ColumnModel::get( $columnConfig['_class'] )?->format( $value, $columnConfig );
			}

			$resource[ $row['key'] ] = $value;
		}

		return $resource;
	}
}
