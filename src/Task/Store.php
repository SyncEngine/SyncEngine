<?php

namespace App\Task;

use App\Model\DatasetModel;
use App\Model\TaskModel;
use App\Service\ExecutionContext;

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
			'action'  => [
				'label'   => 'Action',
				'type'    => 'select',
				'default' => 'set',
				'choices' => [
					'set' => 'Set dataset',
					'get' => 'Get dataset',
				],
			],
			'dataset' => [
				'label'   => 'Dataset',
				'type'    => 'entity',
				'entity'  => 'dataset',
				'actions' => [ 'edit', 'create' ],
			],
			'key'     => [
				'label'    => 'Data key',
				'type'     => 'text', // @todo Column/Key selection field type.
				'taggable' => true,
			],
			'path'    => [
				'label' => 'Dataset key/path',
				'type'  => 'text',
				'help'  => 'Set the path where this value will be stored or leave empty. Use dots (.) to traverse into the dataset.',
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
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

		$key    = $config['key'] ?? '';
		$action = $config['action'] ?? false;
		$path   = $config['path'] ?? false;

		if ( 'get' === $action ) {
			if ( $path ) {
				$value = $dataset->getData( explode( '.', $path ) );
			} else {
				$value = $dataset->getData();
			}

			if ( $key ) {
				$data[ $key ] = $value;
			} else {
				$data = $value;
			}
		} else {
			$value = $data;
			if ( $key ) {
				$value = $value[ $key ] ?? null;
			}

			if ( $path ) {
				$dataset->setData( $value, explode( '.', $path ) );
			} else {
				$dataset->setData( $value );
			}

			$dataset->persist( $context->getEntityManager(), true );
		}

		return $data;
	}
}
