<?php

namespace SyncEngine\Task;

use SyncEngine\Model\ColumnModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\Data\ResourceData;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
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
				'label'       => $this->trans( 'Key / Column name' ),
				'help'        => [
					$this->trans( 'The data column key name for the values that needs to be set' ),
					$this->trans( 'Nested keys are supported: {example}', [ 'example' => 'key.nested_key', ] ),
					$this->trans( 'Leave empty for root' ),
				],
				'type'        => 'text',
				'taggable'    => true,
			],
			'reorder'  => [
				'label' => $this->trans( 'Reorder data?' ),
				'help'  => $this->trans(
					'This will append all configured columns at the end of the current data.'
				),
				'type'  => 'checkbox',
			],
			'force'  => [
				'label' => $this->trans( 'Force if invalid?' ),
				'help'  => [
					$this->trans(
						'When existing data is scalar, create new data collection from values set in this task.'
					),
					$this->trans( 'You can still reference the original value through the {{ data|String }} tag.' )
				],
				'type'  => 'checkbox',
			],
			'params'   => [
				'label'    => '',
				'type'     => 'grid',
				'taggable' => true,
				'sortable' => true,
				'columns'  => [
					'key'    => $this->trans( 'Column key/name' ),
					'value'  => [
						'label'        => $this->trans( 'Value' ),
						'customizable' => true,
						'selectLabel'  => $this->trans( '-- Unchanged --' ),
						'help'         => $this->trans( 'Use {wildcard} to insert the current value.', [ 'wildcard' => '{*value*}' ] ),
						'choices'      => [
							'{*unset*}' => $this->trans( 'Unset' ),
						],
					],
					'column' => [
						'label' => $this->trans( 'Column Type' ),
						'type'  => 'column',
					],
				],
			],
		];
	}

	public function execute( array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		$key     = $config['key'] ?? null;
		$params  = $config['params'];
		$reorder = ! empty( $config['reorder'] );
		$force   = ! empty( $config['force'] );

		$data->set( $this->_execute( $params, $context, $data->get( $key ), $reorder, $force ), $key );

		return $data;
	}

	protected function _execute( iterable $params, ExecuteContext $context, mixed $resource, $reorder = false, $force = false ): mixed
	{
		if ( ! is_iterable( $resource ) ) {
			if ( $force ) {
				$resource = new ResourceData();
			} else {
				if ( null === $resource ) {
					$context->addError( $this->trans( 'Data key not found' ) );
				} else {
					$context->addError( $this->trans( 'Data is not iterable' ) );
				}

				return $resource;
			}
		}

		if ( empty( $params ) ) {
			$context->addError( $this->trans( 'No set params configured' ) );

			return $resource;
		}

		if ( ! $resource instanceof ResourceData ) {
			$resource = ResourceData::create( $resource );
		}

		/**
		 * Convert to iterable and place the raw value as key.
		 * @todo Opt-in?
		 */
		if ( $resource instanceof ExecuteData && $resource->isRaw() ) {
			$resource = new ExecuteData( [ 'raw' => $resource->get() ] );
		}

		foreach ( $params as $row ) {
			if ( ! isset( $row['key'] ) ) {
				continue;
			}

			$key   = $row['key'];
			$value = $row['value'] ?? null;

			if ( '{*unset*}' === $value ) {
				unset( $resource[ $row['key'] ] );
				continue;
			}

			$current = $resource[ $key ] ?? null;
			if ( ! $value && '0' !== (string) $value ) {
				$value = $current;
			} elseif ( is_string( $value ) && str_contains( $value, '{*value*}' ) ) {
				$value = str_replace( '{*value*}', (string) $current, $value );
			}

			if ( $value instanceof ResourceData ) {
				$value = $value->get();
			}

			$columnConfig = $row['column'] ?? '';
			if ( $columnConfig ) {
				$value = ColumnModel::get( $columnConfig['_class'] )?->format( $value, $columnConfig );
			}

			if ( $reorder ) {
				// Unset current value so the new value will be appended.
				unset( $resource[ $key ] );
			}

			$resource[ $key ] = $value;
		}

		return $resource->get();
	}
}
