<?php

namespace App\Controller;

use App\Model\Interface\Exportable;
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
	protected function _handleRequest( Persistable $model, Request $request, EntityManagerInterface $entityManager ): array
	{
		$action = $request->request->get( 'action' );
		$return = [];

		switch ( $action ) {
			case 'delete':
				// @todo
			break;

			case 'form':
			case 'create':
			case 'edit':
				$form = $this->form( $model, $request, $entityManager, false );

				if ( $form->isSubmitted() ) {
					$return['success'] = $form->isValid();
				}

				$return['entity'] = $model->normalize( true, true );
				$return['html']   = $this->render( '_partials/form.html.twig', [
					'form' => $form,
				] );
			break;

			case 'export':
				if ( $model instanceof Exportable && $model->getId() ) {
					$return['success'] = true;
					$return['export']  = $model->export();
				} else {
					$return['success'] = false;
				}
			break;

			case 'query':
			case 'list':
				$query   = $request->request->get( 'query' );
				$query   = $query ? json_decode( $query, true ) : null;
				$results = $this->_handleActionList( $model, $query );

				if ( ! empty( $query['total'] ) ) {
					$return['total'] = $this->_handleActionTotal( $model, $query );
				}

				$return['data']    = $results;
				$return['success'] = true;
			break;
		}

		return $return;
	}

	protected function _handleActionList( $model, $query ): array
	{
		$results = $model::getAll( $query );

		if ( $results ) {
			foreach ( $results as $key => $item ) {
				$results[ $key ] = $item->normalize(
					$query['dependencies'] ?? $query['relations'] ?? false,
					$query['dependents'] ?? $query['relations'] ?? false
				);
			}
		}

		return $results;
	}

	protected function _handleActionTotal( $model, $query ): int
	{
		return $model::getTotalCount( $query );
	}

	protected function _handleForm( Persistable $model, FormInterface|string $form, Request $request, EntityManagerInterface $entityManager, $saveLabel = '' ): FormInterface|bool
	{
		if ( ! $form instanceof FormInterface ) {
			$form   = $this->createForm( $form, $model->getEntity(), [ 'attr' => [ 'data-id' => $model->getId() ] ] );
		}

		if ( false !== $saveLabel ) {
			if ( ! $saveLabel ) {
				$saveLabel = ( $model->getId() ) ? 'Update' : 'Create';
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
