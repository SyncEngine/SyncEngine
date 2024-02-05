<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use SyncEngine\Service\ExecutePreview;
use SyncEngine\Service\Preferences;
use SyncEngine\Service\Provider\Blueprints;
use SyncEngine\Service\Provider\Tasks;
use SyncEngine\Service\Provider\Webservices;

class JsonController extends DefaultController
{
	#[Route( '/json/preview', name: 'json_preview', requirements: [] )]
	public function preview( ExecutePreview $executePreview, Request $request = null ): JsonResponse
	{
		return $this->json( $executePreview->preview( $request ) );
	}

	#[Route( '/json/preferences', name: 'json_preferences' )]
	public function handlePreferences( Request $request, Preferences $preferences ): JsonResponse
	{
		if ( ! $preferences->exists() ) {
			return $this->json( [
				'success' => false,
			] );
		}

		$action = $request->get( 'action' );
		if ( 'update' === $action ) {
			$setting = $request->get( 'setting' );
			$value   = $request->get( 'value' );

			$preferences->update( $setting, $value );
		}

		return $this->json( [
			'success' => true,
			'data'    => $preferences->fetch(),
		] );
	}

	#[Route( '/json/blueprints', name: 'json_blueprints' )]
	public function getBlueprints( Blueprints $blueprintsService ): JsonResponse
	{
		return $this->json( [
			'success' => true,
			'data'    => $blueprintsService->getNormalized(),
		] );
	}

	#[Route( '/json/tasks', name: 'json_tasks' )]
	public function getTasks( Tasks $tasksService ): JsonResponse
	{
		return $this->json( [
			'success' => true,
			'data'    => $tasksService->getNormalized(),
		] );
	}

	#[Route( '/json/webservices', name: 'json_webservices' )]
	public function getWebservices( Webservices $webservicesService ): JsonResponse
	{
		return $this->json( [
			'success' => true,
			'data'    => $webservicesService->getNormalized(),
		] );
	}
}
