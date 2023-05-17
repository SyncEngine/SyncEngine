<?php

namespace App\Controller\Task;

use App\Controller\TaskController;
use App\Entity\Connection;



class Retriever extends TaskController
{
	public $type = 'retriever';
	public $name = 'Retriever';
	public $description = 'Retrieve your data from your specific connection';

	function getFields(): array
	{
		$doctrine = $GLOBALS['app']->getContainer()->get('doctrine');
		$connections = $doctrine->getManager()->getRepository(Connection::class)->findAll();

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

	function execute(array $config, array $data): array
	{
		// TODO: Implement execute() method.
	}
}
