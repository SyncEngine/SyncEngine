<?php

namespace App\Controller;

use App\Model\Interface\Persistable;
use App\Service\ModelImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Annotation\Route;

class EntityController extends AdminController
{
	protected function _handleJson( $model, Request $request, EntityManagerInterface $entityManager  ): array
	{
		$id     = $model->getId();
		$action = $request->request->get( 'action' );
		$json   = [];

		switch ( $action ) {
			case 'delete':
				// @todo
			break;

			case 'form':
			case 'create':
			case 'edit':
				$form = $this->form( $model, $request, $entityManager, false );

				if ( $form->isSubmitted() ) {
					$json['success'] = $form->isValid();
				}

				$json['automation'] = $model->normalize();
				$json['html']       = $this->render( '_partials/form.html.twig', [
					'form' => $form,
				] );
			break;

			case 'export':
				if ( $id ) {
					$json['success'] = true;
					$json['export']  = $model::get( $id )->export();
				} else {
					$json['success'] = false;
				}
			break;

			case 'query':
			case 'list':
				$query   = $request->request->get( 'query' );
				$query   = $query ? json_decode( $query, true ) : null;
				$results = $model::getAll( $query );

				if ( $results ) {
					foreach ( $results as $key => $item ) {
						$results[ $key ] = $item->normalize( $query['relations'] ?? false, $query['dependents'] ?? false );
					}
				}

				if ( ! empty( $query['total'] ) ) {
					$json['total'] = $model::getTotalCount( $query );
				}

				$json['data']    = $results;
				$json['success'] = true;
			break;
		}

		return $json;
	}

	protected function _handleForm( $model, $formClass, Request $request, EntityManagerInterface $entityManager, $saveLabel = '' ): FormInterface|bool
	{
		$entity = $model->getEntity();
		$form = $this->createForm( $formClass, $entity, [ 'attr' => [ 'data-id' => $entity->getId() ] ] );
		if ( false !== $saveLabel ) {
			if ( ! $saveLabel ) {
				$saveLabel = ( $entity->getId() ) ? 'Update' : 'Create';
			}
			$form->add( 'save', SubmitType::class, [ 'label' => $saveLabel ] );
		}

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			$model->persist( $entityManager, true );
		}

		return $form;
	}

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
