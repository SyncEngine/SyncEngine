<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\ConnectionModel;
use SyncEngine\Model\DatasetModel;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\ExecutePreview;
use SyncEngine\Service\Provider\Blueprints;
use SyncEngine\Service\Provider\Tasks;
use SyncEngine\Service\Provider\Webservices;
use SyncEngine\Service\Vault;

class JsonController extends DefaultController
{
	#[Route( '/json/preview', name: 'json_preview', requirements: [] )]
	public function preview( ExecutePreview $executePreview, Request $request = null ): JsonResponse
	{
		return $this->json( $executePreview->preview( $request ) );
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
