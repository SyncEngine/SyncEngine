<?php

namespace App\Controller\Task;

use App\Controller\TaskController;
use App\Entity\Connection;
use App\Controller\WebserviceController;

class Retriever extends TaskController
{
	public $type = 'retriever';
	public $name = 'Retriever';
	public $description = 'Retrieve your data from your specific connection';

	public function __construct()
	{
		$this->doctrine = $doctrine = $GLOBALS['app']->getContainer()->get('doctrine');
	}

	function getFields(): array
	{
		$connections = $this->doctrine->getManager()->getRepository(Connection::class)->findAll();

		$conSelector = [];
		foreach ($connections as $connection){
			$conSelector[$connection->getId()] = $connection->getName();
		}

		return [
			'connection' => [
				'type' => 'select',
				'choices' => $conSelector,
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
		$connection = $this->doctrine->getManager()->getRepository(Connection::class)->findOneBy(['id'=>$config['connection']]);
		$connectionConfig = $connection->getConfig();
		switch ($connectionConfig['auth_type']){
			case "ftp":
				$item = $ws->getFTP($connectionConfig,$config);
				break;
			case "basic":
				$item = $ws->basicAuthMethod($connectionConfig,$config, $data);
				break;
		}


		//if retrieved item is not array but a temp file
		if(!is_array($item)){
			$item = [$item];
		}
		return $item;
	}
}
