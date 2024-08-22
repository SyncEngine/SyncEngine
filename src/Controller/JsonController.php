<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Service\Preferences;
use SyncEngine\Service\Provider\Blueprints;
use SyncEngine\Service\Provider\Columns;
use SyncEngine\Service\Provider\Tasks;
use SyncEngine\Service\Provider\Webservices;

class JsonController extends DefaultController
{
	#[Route( '/json/preferences', name: 'json_preferences' )]
	public function handlePreferences( Request $request, Preferences $preferences ): JsonResponse
	{
		if ( ! $preferences->exists() ) {
			return $this->json(
				[
					'success' => false,
				]
			);
		}

		$action = $request->get( 'action' );
		if ( 'update' === $action ) {
			$setting = $request->get( 'setting' );
			$value   = $request->get( 'value' );

			$preferences->update( $setting, $value );
		}

		return $this->json(
			[
				'success' => true,
				'data'    => $preferences->fetch(),
			]
		);
	}

	#[Route( '/json/blueprints', name: 'json_blueprints' )]
	public function getBlueprints( Blueprints $blueprintsService ): JsonResponse
	{
		return $this->json(
			[
				'success' => true,
				'data'    => $blueprintsService->getNormalized(),
			]
		);
	}

	#[Route( '/json/columns', name: 'json_columns' )]
	public function getColumns( Columns $columnsService ): JsonResponse
	{
		return $this->json(
			[
				'success' => true,
				'data'    => $columnsService->getNormalized(),
			]
		);
	}

	#[Route( '/json/tasks', name: 'json_tasks' )]
	public function getTasks( Tasks $tasksService ): JsonResponse
	{
		return $this->json(
			[
				'success' => true,
				'data'    => $tasksService->getNormalized(),
			]
		);
	}

	#[Route( '/json/webservices', name: 'json_webservices' )]
	public function getWebservices( Webservices $webservicesService ): JsonResponse
	{
		return $this->json(
			[
				'success' => true,
				'data'    => $webservicesService->getNormalized(),
			]
		);
	}
}
