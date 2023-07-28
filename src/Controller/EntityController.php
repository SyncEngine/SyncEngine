<?php

namespace App\Controller;

use App\Service\ModelImporter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Routing\Annotation\Route;

class EntityController extends AdminController
{
	#[Route( '/import', name: 'import_entities' )]
	public function import( Request $request, ModelImporter $importer )
	{
		// @todo React component using react-diff-viewer.

		$form = $this->createFormBuilder( [] )->add( 'data', TextareaType::class, [
			'label' => 'JSON data',
			'attr'  => [ 'rows' => 15 ],
		] )->add( 'submit', SubmitType::class, [ 'label' => 'Import' ] )->getForm();

		$form->handleRequest( $request );

		if ( $form->isSubmitted() && $form->isValid() ) {
			$data = $form['data']->getData();

			if ( $data ) {
				$data = json_decode( $data, true );
				$importer->import( $data );
			}

			return $this->redirectToRoute( 'app_index' );
		}

		return $this->render( 'admin/import.html.twig', [
			'form'        => $form,
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'import_entities' ),
					'title' => 'Import',
				],
			],
		] );
	}

	public static function getEntityModelClass( $entity ): string
	{
		if ( is_object( $entity ) ) {
			$entity = self::getEntityReflection( $entity )->getShortName();
		}
		return '\\App\\Model\\' . $entity . 'Model';
	}

	public static function getEntityReflection( $entity ): \ReflectionClass
	{
		if ( is_object( $entity ) ) {
			$entity = get_class( $entity );
		}
		return \Doctrine\Common\Util\ClassUtils::newReflectionClass( $entity );
	}

	public static function getEntityClass( $entity ): string
	{
		if ( is_object( $entity ) ) {
			$entity = get_class( $entity );
		}
		return \Doctrine\Common\Util\ClassUtils::getRealClass( $entity );
	}
}
