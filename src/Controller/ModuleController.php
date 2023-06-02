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
		return null !== $this->getTask( $name );
	}

	public function getTask( string $name ): Task|null
	{
		$tasks = $this->getTasks();
		if ( isset( $tasks[ $name ] ) ) {
			return $tasks[ $name ];
		}
		return null;
	}

	public function getTasks(): array
	{
		$tasks   = [];
		$class   = get_class( $this );
		$namespace = explode( "\\", $class );
		array_pop( $namespace );
		$classes = $this->getClassesInNamespace(  implode( "\\", $namespace ) . "\\Task" );

		foreach ( $classes as $class ) {
			$tasks[] = new $class();
		}
		return $tasks;
	}

	public function executeTask( string $task, array $config, array $data ): array
	{
		$task = $this->getTask( $task );

		return $task->execute( $config, $data );
	}
}
