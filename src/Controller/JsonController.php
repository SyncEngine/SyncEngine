<?php

namespace SyncEngine\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Model\ConnectionModel;
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

	#[Route( '/json/webservice/retrieve', name: 'json_webservice_retrieve' )]
	public function handleWebservice( Request $request ): JsonResponse
	{
		$id = $request->get( 'id' );
		if ( ! $id ) {
			$id = $request->get( 'connection' );
			if ( ! $id ) {

				$config = $request->get( 'config' );
				if ( is_string( $config ) ) {
					$config = json_decode( $config, true );
				}

				$id = $config['id'] ?? $config['connection'] ?? null;
			}
		}

		if ( ! $id ) {
			return $this->json(
				[
					'success' => false,
					'error'   => $this->trans( 'Connection required' ),
				],
				Response::HTTP_BAD_REQUEST
			);
		}

		$connection = ConnectionModel::get( $id );
		if ( ! $connection ) {
			return $this->json(
				[
					'success' => false,
					'error'   => $this->trans( 'Connection not found' ),
				],
				Response::HTTP_NOT_FOUND
			);
		}

		$webservice = $connection->getWebservice();
		if ( ! $webservice ) {
			return $this->json(
				[
					'success' => false,
					'error'   => $this->trans( 'Webservice not found' ),
				],
				Response::HTTP_NOT_FOUND
			);
		}

		$response = $webservice->handleRequest( $request, $connection );

		return $response instanceof JsonResponse ? $response : new JsonResponse( $response->getContent(), $response->getStatusCode() );
	}
}
