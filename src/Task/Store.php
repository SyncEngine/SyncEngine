<?php

namespace App\Task;

use App\Component\AutomationContext;
use App\Controller\DefaultController;
use App\Model\TaskModel;
use App\Service\DatasetService;

class Store extends TaskModel
{
	public function __construct()
	{
		$this->type = 'store';
		$this->name = 'Store';
		$this->description = 'Get or set a dataset';

		parent::__construct();
	}

	public function getFields(): array {
		$datasets = [];
		foreach ( DatasetService::getDatasets() as $dataset ) {
			$datasets[ $dataset->getId() ] = $dataset->getName();
		}

		return [
			'action' => [
				'label' => 'Action',
				'type' => 'select',
				'default' => 'set',
				'choices' => [
					'set' => 'Set dataset',
					'get' => 'Get dataset',
				]
			],
			'dataset' => [
				'label' => 'Dataset',
				'type' => 'select',
				'choices' => $datasets,
			],
			'key' => [
				'label' => 'Data key',
				'type' => 'text', // @todo Column/Key selection field type.
			],
		];
	}

	function execute( array $config, AutomationContext $context, $data )
	{
		$dataset = DatasetService::getDataset( $config['dataset'] );

		if ( ! $dataset ) {
			// @todo error?
			return $data;
		}

		$key = $config['key'] ?? '';
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

			// @todo Use a model to parse new data.
			$dataset->setData( $value );

			// @todo Refactor so we don't need DefaultController?
			DefaultController::getEntityManager()->persist( $dataset->getEntity() );
			DefaultController::getEntityManager()->flush();
		}

		return $data;
	}
}
