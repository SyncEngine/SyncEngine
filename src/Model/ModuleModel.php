<?php

namespace App\Model;

use App\Controller\DefaultController;

abstract class ModuleModel
{
	public $name = '';
	public $label = '';
	public $description = '';
	public $author = '';
	public $version = '';

	public function __construct()
	{
		// Construct.
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

	public function executeTask( string $task, array $config, array $data ): array
	{
		$task = $this->getTask( $task );

		return $task->execute( $config, $data );
	}

	public function hasTask( string $name ): bool
	{
		return null !== $this->getTask( $name );
	}

	public function getTask( string $name ): TaskModel|null
	{
		$tasks = $this->getTasks();
		if ( isset( $tasks[ $name ] ) ) {
			return $tasks[ $name ];
		}
		return null;
	}

	final public function getTasks(): array
	{
		if ( TaskModel::isTask( $this ) ) {
			return [ $this ];
		}

		$tasks   = [];
		$class   = get_class( $this );
		$namespace = explode( "\\", $class );
		array_pop( $namespace );
		$classes = DefaultController::getClassesInNamespace(  implode( "\\", $namespace ) . "\\Task" );

		foreach ( $classes as $class ) {
			$task = new $class();
			if ( TaskModel::isTask( $task ) ) {
				$tasks[] = $task;
			}
		}
		return $tasks;
	}

	public function getName():string
	{
		return $this->name;
	}

	public function getLabel():string
	{
		return $this->label ?? $this->getName();
	}

	public function getDescription():string
	{
		return $this->description;
	}

	public function getAuthor():string
	{
		return $this->author;
	}

	public function getVersion():string
	{
		return $this->version;
	}

	final static function isModule( $class ): bool
	{
		return $class instanceof ModuleModel;
	}
}
