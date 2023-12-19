<?php

namespace SyncEngine\Model;

use SyncEngine\Controller\DefaultController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

abstract class ModuleModel extends AbstractBundle
{
	//protected $name;
	const name = '';
	const description = '';
	const author = '';
	const version = '';

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

	public function update(): bool
	{
		return true;
	}

	public function handleRequest( Request $request ): ?Response
	{
		return null;
	}

	public function renderRequest( Request $request ): Response|array|null
	{
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
				$tasks[ $task->getClassLocator() ] = $task;
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
				$webservices[ $webservice->getClassLocator() ] = $webservice;
			}
		}

		return $webservices;
	}

	public function getModuleName(): string
	{
		return $this::name ?? $this->getName();
	}

	public function getDescription(): string
	{
		return $this::description;
	}

	public function getAuthor(): string
	{
		return $this::author;
	}

	public function getVersion(): string
	{
		return $this::version;
	}

	final static function isModule( $class ): bool
	{
		return $class instanceof ModuleModel;
	}

	final public static function getClassLocator(): string
	{
		return static::_getClassLocator();
	}

	final public static function _getClassLocator(): string
	{
		return ( new \ReflectionClass( static::class ) )->getShortName();
	}
}
