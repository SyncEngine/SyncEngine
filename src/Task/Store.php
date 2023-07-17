<?php

namespace App\Task;

use App\Component\ExecutionContext;
use App\Model\DatasetModel;
use App\Model\TaskModel;

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
		$datasets = [];
		foreach ( DatasetModel::getAll() as $dataset ) {
			$datasets[ $dataset->getId() ] = $dataset->getName();
		}

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
				'choices' => $datasets,
			],
			'key'     => [
				'label' => 'Data key',
				'type'  => 'text', // @todo Column/Key selection field type.
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
	{
		$dataset = DatasetModel::get( $config['dataset'] );

		if ( ! $dataset ) {
			// @todo error?
			return $data;
		}

		$key    = $config['key'] ?? '';
		$action = $config['action'] ?? false;

		if ( 'get' === $action ) {
			$value = $dataset->getData();
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

			$dataset->setData( $value );
			$dataset->persist( $context->getEntityManager(), true );
		}

		return $data;
	}
}
