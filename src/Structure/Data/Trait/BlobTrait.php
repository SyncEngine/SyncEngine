<?php

namespace SyncEngine\Structure\Data\Trait;

use SyncEngine\Service\BlobStore;
use SyncEngine\Structure\ValueObject\Blob;

trait BlobTrait
{
	/**
	 * Register a Blob with the active BlobStore and return its normalized marker.
	 */
	protected function registerBlob( Blob $blob ): array
	{
		$store = BlobStore::getInstance();

		$store->register( $blob );

		return $blob->normalize();
	}

	/**
	 * Rehydrate a Blob from its normalized marker array via BlobStore.
	 */
	protected function rehydrateBlobFromMarker( array $marker ): ?Blob
	{
		$store = BlobStore::getInstance();

		return $store->rehydrate( $marker );
	}
}
