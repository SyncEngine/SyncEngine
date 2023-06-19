<?php

namespace App\Model;

use App\Controller\DefaultController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

abstract class ModuleModel extends AbstractBundle
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

	public function renderRequest( Request $request ): Response|null
	{
		// @todd https://symfony.com/doc/current/bundles.html
		return null;
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
		$tasks     = [];
		$namespace = ( new \ReflectionClass( $this ) )->getNamespaceName();
		$classes = DefaultController::getClassesInNamespace(  $namespace . "\\Task" );

		foreach ( $classes as $class ) {
			$task = new $class();
			if ( TaskModel::isTask( $task ) ) {
				$task->setModule( $this );
				$tasks[] = $task;
			}
		}
		return $tasks;
	}

	public function hasWebservice( string $name ): bool
	{
		return null !== $this->getWebservice( $name );
	}

	public function getWebservice( string $name ): WebserviceModel|null
	{
		$webservices = $this->getWebservices();
		if ( isset( $webservices[ $name ] ) ) {
			return $webservices[ $name ];
		}
		return null;
	}

	final public function getWebservices(): array
	{
		if ( WebserviceModel::isWebservice( $this ) ) {
			return [ $this ];
		}

		$webservices     = [];
		$namespace = ( new \ReflectionClass( $this ) )->getNamespaceName();
		$classes = DefaultController::getClassesInNamespace(  $namespace . "\\Webservice" );

		foreach ( $classes as $class ) {
			$webservice = new $class();
			if ( WebserviceModel::isWebservice( $webservice ) ) {
				$webservice->setModule( $this );
				$webservices[] = $webservice;
			}
		}
		return $webservices;
	}

	public function getLabel(): string
	{
		return ( ! empty( $this->label ) ) ? $this->label : $this->getName();
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function getAuthor(): string
	{
		return $this->author;
	}

	public function getVersion(): string
	{
		return $this->version;
	}

	final static function isModule( $class ): bool
	{
		return $class instanceof ModuleModel;
	}
}
