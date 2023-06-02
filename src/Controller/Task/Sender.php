<?php

namespace App\Controller\Task;

use App\Controller\DefaultController;
use App\Entity\Connection;
use App\Model\Trait\Task;

class Sender
{
	use Task;

	public function __construct()
	{
		$this->type = 'sender';
		$this->name = 'Sender';
		$this->description = 'Send your data to your specific connection';
	}

    function getFields(): array
    {
		// @todo Centralize getting entity field options. Maybe AJAX?
		$connections = ( new DefaultController() )->getEntityManager()->getRepository( Connection::class )->findAll();
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
