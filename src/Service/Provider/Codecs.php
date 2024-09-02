<?php

namespace SyncEngine\Service\Provider;

use SyncEngine\Model\Abstract\ServiceModel;
use SyncEngine\Model\CodecModel;

/**
 * @extends AbstractServiceModelProvider<CodecModel>
 */
class Codecs extends AbstractServiceModelProvider
{
	public function validate( ServiceModel $service ): bool
	{
		return $service instanceof CodecModel;
	}

	public function get( $name ): ?ServiceModel
	{
		// Support all lowercase.
		$service = parent::get( ucfirst( $name ) );

		if ( ! $service ) {
			// @todo Use custom locator for formats.
			$codecs = $this->getAll();
			foreach ( $codecs as $codec ) {
				if ( in_array( $name, $codec->getFormats(), true ) ) {
					return $codec;
				}
			}
		}

		return $service;
	}
}
