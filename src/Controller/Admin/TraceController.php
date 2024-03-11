<?php

namespace SyncEngine\Controller\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Controller\Abstract\EntityController;
use SyncEngine\Model\TraceModel;

class TraceController extends EntityController
{
	#[Route( '/json/trace', 'json_trace' )]
	public function handleJson( Request $request ): JsonResponse
	{
		$id = $request->request->get( 'id' );
		$id = ( $id && is_numeric( $id ) ) ? $id : 0;

		$model = ( $id ) ? TraceModel::get( $id ) : TraceModel::create();

		return $this->_handleJsonRequest( $model, $request );
	}

	public function view( TraceModel $trace ): array
	{
		$info = $trace->normalize();
		$trace = $trace->getFullTrace();

		return [
			'success' => ! empty( $trace ),
			'data' => [
				'Trace' => $trace,
				'Info' => $info,
			]
		];
	}
}
