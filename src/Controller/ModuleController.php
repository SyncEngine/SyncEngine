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

	abstract function hasTask( string $name ): bool;

	abstract function getTask( string $name ): TaskController;

	public function executeConfig( array $config, array $data ): array
	{
		$task = $config['task'] ?? null;
		if ( $this->hasTask( $task ) ) {
			// @todo Maybe add task config under own key?
			return $this->executeTask( $task, $config, $data );
		}

		// @todo Add task not found error.

		return $data;
	}

	abstract function executeTask( string $task, array $config, array $data ): array;
}
