<?php

namespace App\Controller\Task;

use App\Controller\TaskController;
use App\Entity\Connection;

class Sender extends TaskController
{
	public $type = 'sender';
	public $name = 'Sender';
	public $description = 'Send your data to your specific connection';

    function getFields(): array
    {
		// @todo Centralize getting entity field options. Maybe AJAX?
		$connections = $this->getEntityManager()->getRepository( Connection::class )->findAll();
		$conSelector = [];
		foreach ( $connections as $connection ) {
			$conSelector[ $connection->getId() ] = $connection->getName();
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
    }

    function execute(array $config, array $data): array
    {
        // TODO: Implement execute() method.
    }
}
