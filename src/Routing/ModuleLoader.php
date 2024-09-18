<?php

namespace SyncEngine\Routing;

use ReflectionClass;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Contracts\Service\Attribute\Required;
use SyncEngine\Service\Provider\Modules;

class ModuleLoader extends Loader
{
	private bool $isLoaded = false;
	private string $root;
	private Modules $modules;

	#[Required]
	public function init(
		#[Autowire( '%syncengine.dir.modules%' )]
		string $root, Modules $modules,
	): void {
		$this->modules = $modules;
		$this->root    = $root;
	}

	/**
	 * @inheritDoc
	 * @return RouteCollection
	 */
	public function load( mixed $resource, ?string $type = null ): mixed
	{
		if ( $this->isLoaded ) {
			throw new \RuntimeException( 'Module routes already loaded' );
		}

		$routesCollection = new RouteCollection();

		foreach ( $this->modules->getAll() as $module ) {
			$reflection = new ReflectionClass( $module );
			$namespace  = $reflection->getNamespaceName();
			$locator    = $module->getClassLocator();

			$module_resource = [
				'path'      => $this->root . '/' . $locator,
				'namespace' => $namespace . '\\Controller',
			];

			$prefix     = 'module/' . strtolower( $locator ); // Postfix with separator.
			$namePrefix = str_replace( '/', '_', $prefix );

			$imported = $this->import( $module_resource, 'attribute' );

			foreach ( $imported as $name => $route ) {
				/** @var Route $route */
				$route->setPath( $prefix . $route->getPath() );
				$routesCollection->add( $namePrefix . '_' . $name, $route );
			}
		}

		$this->isLoaded = true;

		return $routesCollection;
	}

	/**
	 * @inheritDoc
	 */
	public function supports( mixed $resource, ?string $type = null ): bool
	{
		return 'syncengine_modules' === $type;
	}
}
