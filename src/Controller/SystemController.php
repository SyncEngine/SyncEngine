<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class SystemController extends AdminController
{
	#[Route( '/system', name: 'system_index' )]
	public function system_index( Request $request, TranslatorInterface $translator ): Response
	{
		return $this->render( 'admin/system/index.html.twig', [
			'header' => 'System',
			'breadcrumbs' => [
				[
					'title' => 'System',
					'current' => true,
				],
			],
		] );
	}

	#[Route( '/system/env', name: 'system_env' )]
	public function system_env( Request $request, TranslatorInterface $translator ): Response
	{
		return $this->render( 'admin/system/index.html.twig', [
			'header' => 'Environment',
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'system_index' ),
					'title' => 'System',
				],
				[
					'title' => 'Environment',
					'current' => true,
				],
			],
		] );
	}
}
