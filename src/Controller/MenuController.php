<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class MenuController extends DefaultController
{
	public function jsonMenu(): JsonResponse
	{
		return $this->json( [] );
	}

	public function renderMainMenu( $currentPath, RouterInterface $router ): Response
	{
		$routes = $this->fetchMenuRoutes( 'main', $router );

		array_multisort( array_column( $routes, 'position' ), SORT_ASC, $routes );

		return $this->render(
			'admin/_partials/menu.twig',
			[
				'items'       => $routes,
				'currentPath' => $currentPath,
			]
		);
	}

	public function fetchMenuRoutes( string $menu, RouterInterface $router ): array
	{
		$routes    = [];
		$allRoutes = $router->getRouteCollection()->all();
		foreach ( $allRoutes as $name => $route ) {
			if ( $menu === $route->getOption( 'menu' ) ) {
				$routes[] = [
					'name'     => $name,
					'link'     => $route->getPath(),
					'title'    => $this->trans( $route->getOption( 'menuTitle' ) ),
					'icon'     => $route->getOption( 'menuIcon' ),
					'parent'   => $route->getOption( 'menuParent' ),
					'position' => (float) $route->getOption( 'menuPosition' ),
				];
			}
		}

		return $routes;
	}
}
