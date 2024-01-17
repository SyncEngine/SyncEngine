<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\ResourceData;
use SyncEngine\Service\Slug;

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
						'selectLabel'  => '-- Unchanged --',
						'choices'      => [
							'{%unset%}' => 'Unset',
						],
					],
					'column' => [
						'label'        => 'Column Type',
						'customizable' => false,
						'selectLabel'  => '-- Unchanged --',
						'choices'      => [
							'boolean' => 'Boolean',
							'number'  => [
								'label' => 'Number',
								'choices' => [
									'numeric' => 'Any number',
									'float'   => 'Decimal',
									'int'     => 'Integer',
								],
							],
							'string'  => [
								'label' => 'String',
								'choices' => [
									'string'           => 'Text',
									'capitalize_first' => 'Capitalize first',
									'capitalize_words' => 'Capitalize Words',
									'uppercase'        => 'UPPERCASE',
									'lowercase'        => 'lowercase',
									'slugify'          => 'lowercase-dash',
									'constant'         => 'UPPERCASE_UNDERSCORE',
									'snakecase'        => 'snake_case',
									'camelcase'        => 'CamelCase',
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

			$value = $value ?: $resource[ $row['key'] ];

			if ( $column ) {
				switch ( $column ) {
					case 'string':
						$value = (string) $value;
					break;
					case 'numeric':
						if ( is_numeric( $value ) && floor( $value ) != $value ) {
							return (float) $value;
						}
						return (int) $value;
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
						$value = ( new Slug() )->slugify( $value );
						$value = strtoupper( $value );
						$value = str_replace( '-', '_', $value );
					break;
					case 'snakecase':
						$value = ( new Slug() )->slugify( $value );
						$value = str_replace( '-', '_', $value );
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
