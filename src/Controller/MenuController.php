<?php

namespace SyncEngine\Controller;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Service\Menu\MenuLoader;

class MenuController extends DefaultController
{
	public function __construct(
		private Security $security,
	) {}

	#[Route( '/json/menu/{menuName}', name: 'json_menu' )]
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
		$items   = [];
		$isAdmin = $this->security->isGranted( 'ROLE_ADMIN' );

		// @todo filter according to the security
		foreach ( $loader->getMenuItems( $menuName )->all() as $item ) {
			$link = $this->generateUrl( $item->getRoute(), $item->getParameters() );
			if ( $isAdmin or ! str_contains( $link, "admin" ) ) {
				$items[] = [
					'name'     => $item->getRoute(),
					'link'     => $link,
					'title'    => $this->trans( $item->getLabel() ),
					'icon'     => $item->getIcon(),
					'parent'   => $item->getParent() ?: 'system',
					'position' => (float) ( is_numeric( $item->getPosition() ) ? $item->getPosition() : 10 ),
				];
			}
		}

		return $items;
	}
}
