<?php

namespace App\Controller;

abstract class ModuleController extends DefaultController
{
	public $name = '';
	public $label = '';
	public $descrioption = '';
	public $author = '';
	public $version = '';

	public function __construct() {

	}

	public function executeConfig( array $config, array $data ): array
	{
		$task = $config['task'] ?? null;
		if ( $this->hasTask( $task ) ) {
			return $this->executeTask( $task, $config, $data );
		}

		// @todo Add task not found error.

		return $data;
	}

	public function hasTask( string $name ): bool
	{
		return $this->getTask( $name ) instanceof TaskController;
	}

	abstract function getTask( string $name ): TaskController;

	abstract function executeTask( string $task, array $config, array $data ): array;
}
