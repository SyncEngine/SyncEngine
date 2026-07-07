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
			} elseif ( isset( $config['steps'] ) || isset( $config['wizard'] ) ) {
				$config['type'] = 'wizard';
			}
		}

		if ( isset( $config['nested'] ) ) {
			return new NestedGroupFieldType( $config );
		}

		if ( isset( $config['fields'] ) ) {
			return new GroupFieldType( $config );
		}

		return match ( $config['type'] ?? '' ) {
			default => new InputFieldType( $config ),
		};
	}
}
