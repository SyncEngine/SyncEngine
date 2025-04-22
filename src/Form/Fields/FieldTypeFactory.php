<?php

namespace SyncEngine\Form\Fields;

use SyncEngine\Form\Fields\Interface\FieldConfigInterface;

class FieldTypeFactory
{
	public function create( array|FieldConfigInterface $config ): FieldConfigInterface
	{
		if ( $config instanceof FieldConfigInterface ) {
			return $config;
		}

		if ( ! isset( $config['type'] ) ) {
			if ( isset( $config['tabs'] ) ) {
				$config['type'] = 'tabs';
			}
		}

		return match ( $config['type'] ?? '' ) {
			default => new InputFieldType( $config ),
		};
	}
}
