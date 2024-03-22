<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\Slug;
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
					'format' => [
						'label'        => $this->trans('Format Type'),
						'customizable' => false,
						'selectLabel'  => $this->trans('-- Unchanged --'),
						'choices'      => [
							'boolean' => 'Boolean',
							'number'  => [
								'label' => $this->trans('Number'),
								'choices' => [
									'numeric' => $this->trans('Any number'),
									'float'   => $this->trans('Decimal'),
									'int'     => $this->trans('Integer'),
								],
							],
							'string'  => [
								'label' => 'String',
								'choices' => [
									'string'           => $this->trans('Text'),
									'capitalize_first' => $this->trans('Capitalize first'),
									'capitalize_words' => $this->trans('Capitalize Words'),
									'uppercase'        => $this->trans('UPPERCASE'),
									'lowercase'        => $this->trans('lowercase'),
									'slugify'          => $this->trans('lowercase-dash'),
									'constant'         => $this->trans('UPPERCASE_UNDERSCORE'),
									'snakecase'        => $this->trans('snake_case'),
									'camelcase'        => $this->trans('CamelCase'),
								],
							],
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

			if ( ! $value ) {
				$value = $resource[ $row['key'] ];
			} else {
				$value = str_replace( '{%value%}', $resource[ $row['key'] ], $value );
			}

			if ( $column ) {
				switch ( $column ) {
					case 'string':
						$value = (string) $value;
					break;
					case 'numeric':
						if ( is_numeric( $value ) && floor( $value ) != $value ) {
							$value = (float) $value;
						} else {
							$value = (int) $value;
						}
					break;
					case 'float':
						$value = (float) $value;
					break;
					case 'int':
						$value = (int) $value;
					break;
					case 'boolean':
						$value = (bool) $value;
					break;
					case 'uppercase':
						$value = strtoupper( (string) $value );
					break;
					case 'lowercase':
						$value = strtolower( (string) $value );
					break;
					case 'capitalize_first':
						$value = ucfirst( (string) $value );
					break;
					case 'capitalize_words':
						$value = ucwords( (string) $value );
					break;
					case 'slugify':
						$value = ( new Slug() )->slugify( $value );
					break;
					case 'constant':
						$value = ( new Slug() )->slugify( $value, '_' );
						$value = strtoupper( $value );
					break;
					case 'snakecase':
						$value = ( new Slug() )->slugify( $value, '_' );
					break;
					case 'camelcase':
						$value = ( new Slug() )->slugify( $value );
						$value = str_replace( '-', ' ', $value );
						$value = ucwords( $value );
						$value = str_replace( ' ', '', $value );
					break;
				}
			}

			$resource[ $row['key'] ] = $value;
		}

		return $resource;
	}
}
