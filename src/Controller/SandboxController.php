<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Service\ExecutePreview;
use SyncEngine\Service\Sandbox\EntityManagerSandbox;

class SandboxController extends DefaultController
{
	public static function getSubscribedServices(): array
	{
		return array_merge(
			parent::getSubscribedServices(),
			[
				'entitymanager' => '?' . EntityManagerSandbox::class,
			]
		);
	}

	#[Route( '/json/preview', name: 'json_preview', requirements: [] )]
	public function preview( ExecutePreview $executePreview, ?Request $request = null ): JsonResponse
	{
		return $this->json( $executePreview->preview( $request ) );
	}
}
