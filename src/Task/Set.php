<?php

namespace App\Task;

use App\Model\TaskModel;
use App\Service\ExecutionContext;
use App\Service\ResourceData;

class Set extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'modifier';
		$this->name        = 'Set';
		$this->description = 'Set your own values';

		parent::__construct();
	}

	public function getFields(): array
	{
		return [
			'override'    => [
				'label'   => 'Override existing',
				'type'    => 'checkbox',
			],
			'key'       => [
				'label'        => 'Data key',
				'description'  => 'The data key for the values that needs to be set',
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

	public function execute( array $config, ExecutionContext $context, array $data ): array
	{
		$data = new ResourceData( $data );

		$resource = $data;
		$key  = $config['key'] ?? null;

		if ( $key ) {
			$resource = $resource->get( $key );

			if ( ! is_iterable( $data ) ) {
				$context->addError( 'Data key not iterable' );
				return $data->get();
			}

			$resource = new ResourceData( $resource );
		}

		foreach ( $config['params'] as $map ) {
			if ( ! isset( $map['key'] ) ) {
				continue;
			}

			$value = $map['value'] ?? '';

			$resource->set( $value, $map['key'] );
		}

		$data->set( $resource->get(), $key ?? null );

		return $data->get();
	}
}
