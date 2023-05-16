<?php

namespace App\Controller\Task;

use App\Controller\TaskController;

class Sender extends TaskController
{
	public $type = 'sender';
	public $name = 'Sender';
	public $description = 'Send your data to your specific connection';

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
    }

    function execute(array $config, array $data): array
    {
        // TODO: Implement execute() method.
    }
}
