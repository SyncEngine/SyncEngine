<?php

namespace App\Model;

use App\Controller\DefaultController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

abstract class ModuleModel extends AbstractBundle
{
	//protected $name;
	public $moduleName = '';
	public $description = '';
	public $author = '';
	public $version = '';

	public function __construct()
	{
		// Construct.
	}

	public function install(): bool
	{
		return true;
	}

	public function uninstall(): bool
	{
		return true;
	}

	public function getService( $name ): mixed
	{
		return DefaultController::get( $name );
	}

	public function handleRequest( Request $request ): ?Response
	{
		return null;
	}

	public function renderRequest( Request $request ): Response|array|null
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
		$namespace = $this->getNamespace();
		$class     = $namespace . '\\Task\\' . $name;

		if ( class_exists( $class ) ) {
			$task = new $class();
			if ( TaskModel::isTask( $task ) ) {
				return $task;
			}
		}

		return null;
	}

	/**
	 * @return TaskModel[]
	 */
	final public function getTasks(): array
	{
		$tasks     = [];
		$namespace = $this->getNamespace();
		$classes   = DefaultController::getClassFinder()->getClassesInNamespace( $namespace . "\\Task" );

		foreach ( $classes as $class ) {
			$task = new $class();
			if ( TaskModel::isTask( $task ) ) {
				/* @var TaskModel $task */
				$task->setModule( $this );
				$tasks[ $task->getClassName() ] = $task;
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
		$namespace = $this->getNamespace();
		$class     = $namespace . '\\Webservice\\' . $name;

		if ( class_exists( $class ) ) {
			$webservice = new $class();
			if ( WebserviceModel::isWebservice( $webservice ) ) {
				return $webservice;
			}
		}

		return null;
	}

	/**
	 * @return WebserviceModel[]
	 */
	final public function getWebservices(): array
	{
		$webservices = [];
		$namespace   = $this->getNamespace();
		$classes     = DefaultController::getClassFinder()->getClassesInNamespace( $namespace . "\\Webservice" );

		foreach ( $classes as $class ) {
			$webservice = new $class();
			if ( WebserviceModel::isWebservice( $webservice ) ) {
				/* @var WebserviceModel $webservice */
				$webservice->setModule( $this );
				$webservices[ $webservice->getClassName() ] = $webservice;
			}
		}

		return $webservices;
	}

	public function getModuleName(): string
	{
		return ( ! empty( $this->moduleName ) ) ? $this->moduleName : $this->getName();
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

	final public static function getModuleClass(): string {
		return ( new \ReflectionClass( __CLASS__ ) )->getShortName();
	}
}
