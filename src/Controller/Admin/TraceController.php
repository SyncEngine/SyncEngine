<?php

namespace SyncEngine\Controller\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use SyncEngine\Controller\Admin\Abstract\EntityController;
use SyncEngine\Model\Enum\TraceStatus;
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

	public function view( TraceModel $trace, Request $request ): array
	{
		$file = $request->request->get( 'file' );

		if ( $file ) {
			$info = $request->request->get( 'info' );
			$info = ( $info ) ? json_decode( $info, true ) : [];

			$iteration = (int) $request->request->get( 'iteration' );
			$log       = $trace->getStoredLog( $file, $iteration ?: null );

			$info['iteration'] = $iteration;
			$info['trace']     = $trace->normalize();

			return [
				'success' => ! empty( $log ),
				'data' => [
					'Log'  => $log,
					'Info' => $info,
				]
			];
		}

		$fullTrace = $trace->getFullTrace();

		return [
			'success' => ! empty( $fullTrace ),
			'data' => [
				'Trace' => $fullTrace,
				'Info'  => $trace->normalize(),
			]
		];
	}

	public function kill( TraceModel $trace ): array
	{
		$trace->kill();

		// Just for UI, does not story anything.
		$trace->setStatus( TraceStatus::STOPPED );

		return [
			'success' => true,
			'close'   => true,
			'entity'  => $trace->normalize( true, true ),
		];
	}
}
