<?php

namespace SyncEngine\Controller\Admin;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
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
	public function exportSecrets(): JsonResponse
	{
		$secrets = $this->vault->get();

		$this->denyAccessUnlessGranted('ROLE_ADMIN');

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
}
