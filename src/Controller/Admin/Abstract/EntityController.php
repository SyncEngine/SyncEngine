<?php

namespace SyncEngine\Controller\Admin\Abstract;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use SyncEngine\Controller\Admin\AdminController;
use SyncEngine\Model\Interface\Exportable;
use SyncEngine\Model\Interface\Persistable;

abstract class EntityController extends AdminController
{
	protected function _handleJsonRequest( Persistable $model, Request $request ): JsonResponse
	{
		$response = $this->_handleRequest( $model, $request );

		if ( ! $response ) {
			$response = $model->handleRequest( $request );
			if ( $response instanceof JsonResponse ) {
				return $response;
			}
			return $this->json( $response->getContent(), $response->getStatusCode(), $response->headers->all() );
		}

		return $this->json( $response );
	}

	protected function _handleRequest( Persistable $model, Request $request ): array
	{
		$action = $request->request->get( 'action' );
		$return = [];

		// @todo access validations.

		switch ( $action ) {
			case 'delete':
				$return['success'] = $model->delete( true );
			break;

			case 'form':
			case 'create':
			case 'edit':
				if ( ! method_exists( $this, 'form' ) ) {
					$return['success'] = false;
					return $return;
				}

				$form = $this->form( $model, $request, false );

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

			case 'delete|query':
			case 'query':
			case 'list':

				if ( 'delete|query' === $action ) {
					$deleteId = $request->request->get( 'delete' );
					$delete   = $model::get( $deleteId );
					if ( ! $delete ) {
						$return['success'] = false;
						$return['error']   = $this->trans( 'Entity not found: {entity}:{id}', [ $model::getEntityClass(), $deleteId ] );

						return $return;
					}
					$delete->delete( true );
				}

				$query   = $request->request->get( 'query' );
				$query   = $query ? json_decode( $query, true ) : null;
				$results = $this->_handleActionList( $model, $query );

				if ( ! empty( $query['total'] ) ) {
					$return['total'] = $this->_handleActionTotal( $model, $query );
				}

				$return['data']    = $results;
				$return['success'] = true;
			break;

			default:
				if ( method_exists( $this, $action ) ) {
					$return = call_user_func( [ $this, $action ], $model, $request );
				}
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

	protected function _handleForm( Persistable $model, FormInterface|string $form, Request $request, $saveLabel = '' ): FormInterface
	{
		if ( ! $form instanceof FormInterface ) {
			$form = $this->createForm(
				$form,
				$model->getEntity(),
				[
					'attr' => [
						'data-id'     => $model->getId(),
						'data-entity' => json_encode( $model->normalize() ),
					],
				]
			);
		}

		if ( false !== $saveLabel ) {
			if ( ! $saveLabel ) {
				$saveLabel = ( $model->getId() ) ? $this->trans( 'Update' ) : $this->trans( 'Create' );
			}
			$form->add( 'save', SubmitType::class, [ 'label' => $saveLabel ] );
		}

		$form->handleRequest( $request );
		if ( $form->isSubmitted() && $form->isValid() ) {
			/**
			 * @todo Improve model entity state management.
			 *       Mayby use entity manager, or request action to determine action.
			 * @see EntityManager::contains()
			 */
			if ( $model->getId() ) {
				$model->update( true );
			} else {
				$model->save( true );
			}
		}

		return $form;
	}

	public function _getListQuery( Request $request, array $query = [] ): array
	{
		$defaults = [
			'limit'     => 10,
			'total'     => true,
			'relations' => true,
		];

		$query = array_merge( $defaults, $query );

		$page = $request->query->get( 'page' );
		if ( $page ) {
			$query['offset'] = ( $page - 1 ) * $query['limit'];
		}

		return $query;
	}
}
