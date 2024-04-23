<?php

namespace SyncEngine\Service\Menu;

use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ServiceLocator;
use SyncEngine\Attribute\MenuItem;

class MenuLoader
{
	public function __construct(
		private readonly ServiceLocator $container,
	) {}

	public function getMenuItems( string $menu ): MenuItemCollection
	{
		$cache = new FilesystemAdapter();

		/*$items = $cache->get( 'syncengine.menuloader', function( ItemInterface &$item ) {
			return $this->loadMenuItems();
		} );*/

		$items = $this->loadMenuItems();

		$collection = new MenuItemCollection();

		foreach ( $items as $item ) {
			if ( $item->getMenu() === $menu ) {
				$collection->add( $item );
			}
		}

		return $collection;
	}

	protected function loadMenuItems()
	{
		$items = [];

		$controllers = $this->container->getProvidedServices();
		foreach ( $controllers as $tag => $service ) {
			$controller = $this->container->get( $tag );

			$items = array_merge( $items, $this->getControllerMenuItems( $controller ) );
		}

		return $items;
	}

	protected function getControllerMenuItems( AbstractController $controller ): array
	{
		$refClass = new ReflectionClass( $controller );

		$items = $this->getMenuItemAttributes( $refClass );

		foreach ( $refClass->getMethods( ReflectionMethod::IS_PUBLIC ) as $method ) {
			// Only use own methods, not inherited.
			if ( $method->getFileName() === $refClass->getFileName() ) {
				$items = array_merge( $items, $this->getMenuItemAttributes( $method ) );
			}
		}

		return $items;
	}

	/**
	 * @param  ReflectionMethod|ReflectionClass  $reflection
	 *
	 * @return MenuItem[]
	 */
	protected function getMenuItemAttributes( ReflectionMethod|ReflectionClass $reflection ): array
	{
		$menuItems = $reflection->getAttributes( MenuItem::class, \ReflectionAttribute::IS_INSTANCEOF );

		if ( empty( $menuItems ) ) {
			return [];
		}

		$items = [];
		foreach ( $menuItems as $attribute ) {
			/** @var MenuItem $item */
			$items[] = $attribute->newInstance();
		}

		return $items;
	}
}
