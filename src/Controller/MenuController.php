<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Service\Menu\MenuLoader;

class MenuController extends DefaultController
{
	#[Route('/json/menu/{menuName}', name: 'json_menu')]
	public function jsonMenu( string $menuName, MenuLoader $loader ): JsonResponse
	{
		return $this->json( $this->fetchMenuItems( $menuName, $loader ) );
	}

	public function renderMainMenu( $currentPath, MenuLoader $loader ): Response
	{
		$items = $this->fetchMenuItems( 'main', $loader );

		return $this->render(
			'admin/_partials/menu.twig',
			[
				'groups'      => [ 'dashboards', 'entities', 'system' ],
				'items'       => $items,
				'currentPath' => $currentPath,
			]
		);
	}

	public function fetchMenuItems( string $menuName, MenuLoader $loader ): array
	{
		$items    = [];
		foreach ( $loader->getMenuItems( $menuName )->all() as $item ) {
			$items[] = [
				'name'     => $item->getRoute(),
				'link'     => $this->generateUrl( $item->getRoute(), $item->getParameters() ),
				'title'    => $this->trans( $item->getLabel() ),
				'icon'     => $item->getIcon(),
				'parent'   => $item->getParent() ?: 'system',
				'position' => (float) ( is_numeric( $item->getPosition() ) ? $item->getPosition() : 10 ),
			];
		}

		return $items;
	}
}
