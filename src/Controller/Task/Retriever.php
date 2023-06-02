<?php

namespace App\Controller\Task;

use App\Controller\DefaultController;
use App\Controller\WebserviceController;
use App\Entity\Connection;
use App\Model\TaskModel;

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
		// @todo Centralize getting entity field options. Maybe AJAX?
		$connections = ( new DefaultController() )->getEntityManager()->getRepository( Connection::class )->findAll();
		$conSelector = [];
		foreach ( $connections as $connection ){
			$conSelector[ $connection->getId() ] = $connection->getName();
		}

		return [
			'connection' => [
				'type' => 'select',
				'choices' => $conSelector,
			],
			'path' => [
				'type' => 'text',
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

	function execute(array $config, $data): array
	{
		$ws = new WebserviceController();
		$connection = ( new DefaultController() )->getEntityManager()->getRepository(Connection::class)->findOneBy(['id'=>$config['connection']]);
		$connectionConfig = $connection->getConfig();
		switch ($connectionConfig['auth_type']){
			case 'ftp':
				$item = $ws->getFTP($connectionConfig,$config);
				break;
			case 'basic':
				$item = $ws->basicAuthMethod($connectionConfig,$config, $data);
				//ToDO check type of item to encode or not
				$item = json_decode($item);
				break;
			case 'token':
				$item = $ws->bearerToken($connectionConfig,$config, $data);
				//ToDO check type of item to encode or not
				$item = json_decode($item);
				break;
		}

		//if retrieved item is not array but a temp file
		if(!is_array($item)){
			$item = [$item];
		}
		return $item;
	}
}
