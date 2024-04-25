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
	private Modules $modules;
	private string $root;

	#[Required]
	public function init(
		Modules $modules, #[Autowire( '%syncengine.dir.modules%' )]
	string      $root,
	): void {
		$this->modules = $modules;
		$this->root    = $root;
	}

	/**
	 * @inheritDoc
	 */
	public function load( mixed $resource, ?string $type = null )
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
			$prefix          = strtolower( $locator ); // Postfix with separator.
			$namePrefix      = str_replace( '/', '_', $prefix );

			$imported = $this->import( $module_resource, 'attribute' );

			foreach ( $imported as $name => $route ) {
				/** @var Route $route */
				$path = $route->getPath();
				$path = $prefix . $path;
				$route->setPath( $path );

				$routesCollection->add( $namePrefix . '_' . $name, $route );
			}
		}

		$this->isLoaded = true;

		return $routesCollection;
	}

	/**
	 * @inheritDoc
	 */
	public function supports( mixed $resource, ?string $type = null )
	{
		return 'syncengine_modules' === $type;
	}
}
