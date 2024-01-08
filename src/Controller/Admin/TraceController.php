<?php

namespace SyncEngine\Controller\Admin;

use SyncEngine\Controller\Abstract\EntityController;
use SyncEngine\Model\TraceModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TraceController extends EntityController
{
	#[Route( '/json/trace', 'json_trace' )]
	public function handleJson( Request $request, EntityManagerInterface $entityManager ): JsonResponse
	{
		$id = $request->request->get( 'id' );
		$id = ( $id && is_numeric( $id ) ) ? $id : 0;

		$model = ( $id ) ? TraceModel::get( $id ) : TraceModel::create();

		return $this->_handleJsonRequest( $model, $request, $entityManager );
	}
}
