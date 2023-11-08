<?php

namespace App\Task;

use App\Model\DatasetModel;
use App\Model\TaskModel;
use App\Service\ExecutionContext;
use App\Service\ResourceData;

class Store extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'storage';
		$this->name        = 'Store';
		$this->description = 'Get or set a dataset';

		parent::__construct();
	}

	public function getFields(): array
	{
		return [
			'action'    => [
				'label'   => 'Action',
				'type'    => 'select',
				'default' => 'set',
				'choices' => [
					'set' => 'Set dataset',
					'get' => 'Get dataset',
				],
			],
			'dataset'   => [
				'label'   => 'Dataset',
				'type'    => 'entity',
				'entity'  => 'dataset',
				'actions' => [ 'edit', 'create' ],
			],
			'key'       => [
				'label'    => 'Data key',
				'type'     => 'text', // @todo Column/Key selection field type.
				'help'     => 'Nested keys are supported: key[nested_key]',
				'taggable' => true,
			],
			'path'      => [
				'label'    => 'Dataset key/path',
				'type'     => 'text',
				'help'     => 'Set the path where this value will be stored or leave empty. Use dots (.) to traverse into the dataset.',
				'taggable' => true,
			],
			'not_found' => [
				'label'        => 'Not found action',
				'help'         => 'Action if the tag is not found',
				'type'         => 'select',
				'default'      => 'skip',
				'choices'      => [
					'override' => 'Override with empty value',
					'skip'     => 'Skip task',
				],
				'conditionals' => [ 'action' => 'get' ],
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, array $data ): array
	{
		if ( empty( $config['dataset'] ) ) {
			$context->addError( 'No dataset selected' );

			return $data;
		}

		$dataset = DatasetModel::get( $config['dataset'] );

		if ( ! $dataset ) {
			$context->addError( 'Dataset not found' );

			return $data;
		}

		$key       = $config['key'] ?? '';
		$action    = $config['action'] ?? false;
		$path      = $config['path'] ?? false;
		$not_found = $config['not_found'] ?? '';

		if ( 'get' === $action ) {
			if ( $path ) {
				$value = $dataset->getData( explode( '.', $path ) );
			} else {
				$value = $dataset->getData();
			}

			if ( $value || 'override' === $not_found ) {
				if ( $key ) {
					$data = ( new ResourceData( $data ) )->set( $value, $key )->get();
				} else {
					$data = $value;
				}
			}
		} else {
			$value = $data;
			if ( $key ) {
				$value = ( new ResourceData( $data ) )->get( $key );
			}

			if ( is_string( $value ) && ! $path ) {
				// Enforce format type since it's not an array.
				$dataset->setType( 'format' );
				$dataset->setData( [ 'value' => $value ] );
			} else {
				if ( $path ) {
					$dataset->setData( $value, explode( '.', $path ) );
				} else {
					$dataset->setData( $value );
				}
			}

			$dataset->persist( $context->getEntityManager(), true );
		}

		return $data;
	}
}
