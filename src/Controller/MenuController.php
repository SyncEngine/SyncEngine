<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class MenuController extends DefaultController
{
	public function jsonMenu(): JsonResponse
	{
		return $this->json( [] );
	}

	public function renderLeftMain( $currentPath, RouterInterface $router, Request $request ): Response
	{
		$routes    = [];
		$allRoutes = $router->getRouteCollection()->all();
		foreach ( $allRoutes as $route ) {
			if ( $route->getOption( 'menu' ) === "leftMain" ) {
				$routes[] = [
					'title'        => $this->trans($route->getOption( 'menuTitle' )),
					'icon'         => $route->getOption( 'menuIcon' ),
					'link'         => $route->getPath(),
					'menuPosition' => $route->getOption( 'menuPosition' ),
				];
			}
		}

		array_multisort( array_column( $routes, 'menuPosition' ), SORT_ASC, $routes );

		return $this->render(
			'admin/_partials/menu.twig',
			[
				'items'       => $routes,
				'currentPath' => $currentPath,
			]
		);
	}
}
