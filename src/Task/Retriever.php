<?php

namespace App\Task;

use App\Controller\DefaultController;
use App\Controller\WebserviceController;
use App\Entity\Connection;
use App\Model\TaskModel;
use App\Service\WebserviceService;

class Retriever extends TaskModel
{
	public function __construct()
	{
		$this->type = 'retriever';
		$this->name = 'Retriever';
		$this->description = 'Retrieve your data from your specific connection';
	}

	function getFields(): array
	{

		$webservices = ( new WebserviceService() )->getWebservices();

		// @todo Centralize getting entity field options. Maybe AJAX?
		$connections = DefaultController::getEntityManager()->getRepository( Connection::class )->findAll();
		$connectionChoices = [];
		$connectionFields = [];
		foreach ( $connections as $connection ){
			$config = $connection->getConfig();
			if ( isset( $config['webservice'] ) && isset( $webservices[ $config['webservice'] ] ) ) {
				$webservice = $webservices[ $config['webservice'] ];

				$connectionChoices[ $connection->getId() ] = $connection->getName();
				$connectionFields[ $connection->getId() ] = $webservice->getFields();
			}
			// @todo implement webservices.
		}

		return [
			'connection' => [
				'type'    => 'entity',
				'choices' => $connectionChoices,
				'fields'  => $connectionFields,
			],
			'params' => [
				'type' => 'params',
			],
			'header' => [
				'type' => 'params',
			],
			'body' => [
				'type' => 'params',
			],
		];
		// TODO: Implement getFields() method.
	}

	function execute( array $config, $data ): array
	{
		$connectionConfig = $config['connection'];

		if ( ! empty( $connection['id'] ) ) {
			$connection = DefaultController::getEntityManager()->getRepository(Connection::class)->findOneBy(['id'=>$connection['id']]);
			$connectionConfig = array_merge( $connection->getConfig(), $connectionConfig );
		}

		$webservice = ( new WebserviceService() )->getWebservice( $connectionConfig['webservice'] );

		// @todo Option to include in current dataset?
		return $webservice->retrieve( $connectionConfig );
	}
}
