<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SystemController extends AdminController
{
	#[Route( '/system', name: 'system_index' )]
	public function system_index( Request $request ): Response
	{
		return $this->render( 'admin/system/index.html.twig', [
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'import_entities' ),
					'title' => 'Import',
				],
			],
		] );
	}
}
