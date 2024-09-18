<?php

namespace SyncEngine\Controller\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Service\Attribute\Required;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Service\Vault;

class VaultController extends DefaultController
{
	private Vault $vault;

	#[Required]
	public function setVault( Vault $vault ): void
	{
		$this->vault = $vault;
	}

	#[Route( '/json/secrets', name: 'json_secrets' )]
	public function getSecrets(): JsonResponse
	{
		$secrets = $this->vault->get();

		return $this->json( [
			'success' => true,
			'data'    => array_keys( $secrets ),
		] );
	}

	#[Route( '/json/secrets/export', name: 'json_secrets_export' )]
	public function exportSecrets( Request $request ): JsonResponse
	{
		$this->denyAccessUnlessGranted('ROLE_ADMIN');

		$secrets = $this->vault->get( $request->get( 'key' ) );

		return $this->json( [
			'success' => true,
			'data'    => $secrets,
		] );
	}

	#[Route( '/json/secrets/set', name: 'json_secrets_set' )]
	public function setSecret( Request $request ): JsonResponse
	{
		$key = $request->get( 'key' );
		$value = $request->get( 'value' );

		$secrets = $this->vault->get();

		if ( ! $key || ! $value ) {
			$success = false;
		} else {
			$this->vault->set( $key, $value );
			$success = $this->vault->persist();
			if ( $success ) {
				$secrets = $this->vault->get();
			}
		}

		return $this->json( [
			'success' => $success,
			'data'    => array_keys( $secrets ),
		] );
	}

	#[Route( '/json/secrets/unset', name: 'json_secrets_unset' )]
	public function unsetSecret( Request $request ): JsonResponse
	{
		$key = $request->get( 'key' );

		$secrets = $this->vault->get();

		if ( ! $key ) {
			$success = false;
		} else {
			$this->vault->unset( $key );
			$success = $this->vault->persist();
			if ( $success ) {
				$secrets = $this->vault->get();
			}
		}

		return $this->json( [
			'success' => $success,
			'data'    => array_keys( $secrets ),
		] );
	}

	#[Route('system/vault', name: 'system_vault' )]
	function renderVault( Request $request, Vault $vault ): Response
	{
		return $this->render( 'admin/system/vault.html.twig', [
			'backlink'    => $this->generateUrl( 'syncengine_system_index' ),
			'header'      => $this->trans( 'Vault' ),
			'icon'        => 'system-vault',
			'breadcrumbs' => [
				[
					'link'  => $this->generateUrl( 'syncengine_system_index' ),
					'title' => $this->trans( 'System' ),
				],
				[
					'title'   => $this->trans( 'Vault' ),
					'current' => true,
				],
			],
		] );
	}
}
