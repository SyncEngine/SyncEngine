<?php

namespace App\Controller;

use App\Service\ModelImporter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class EntityController extends AdminController
{
	public function removeCircularReference( $entity )
	{
		$defaultContext = [
			AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object): string {
				return $object->getId();
			},
		];

		$normalizer = new ObjectNormalizer( null, null, null, null, null, null, $defaultContext );

		return ( new Serializer( [ $normalizer ] ) )->normalize( $entity );
	}

	#[Route( '/import', name: 'import_entities' )]
	public function import( Request $request, ModelImporter $importer ) {
		$form = $this->createFormBuilder( [] )->add( 'data', TextareaType::class )
				->add( 'submit', SubmitType::class, [ 'label' => 'Import' ] )->getForm();

		$form->handleRequest( $request );

		if ( $form->isSubmitted() && $form->isValid() ) {
			$data = $form['data']->getData();

			if ( $data ) {
				$data = json_decode( $data, true );
				$importer->import( $data );
			}

			return $this->redirectToRoute( 'app_index' );
		}

		return $this->render( 'admin/module/upload.html.twig', [
			'form' => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'import_entities' ),
					'title' => 'Import',
				],
			],
		] );
	}
}
