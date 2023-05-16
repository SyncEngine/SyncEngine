<?php

namespace App\Controller\Task;

use App\Controller\TaskController;
use App\Entity\Connection;
use App\Repository\ConnectionRepository;
use Doctrine\ORM\EntityManagerInterface;

class Retriever extends TaskController
{
	public $type = 'retriever';
	public $name = 'Retriever';
	public $description = 'Retrieve your data from your specific connection';

    function getFields(): array
    {
		//$repository = new ConnectionRepository( Connection::class );
		//$connections = $repository->findAll();

	    // @todo @Niek
	    $connections = [
			'connection ID' => 'connection Name',
	    ];

		return [
			'connection' => [
				'type' => 'entity',
				'choices' => $connections,
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
