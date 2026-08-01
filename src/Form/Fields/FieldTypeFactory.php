<?php

namespace SyncEngine\Form\Fields;

use SyncEngine\Form\Fields\Interface\FieldConfigInterface;
use SyncEngine\Form\Fields\Type\AuthenticationFieldType;
use SyncEngine\Form\Fields\Type\CheckboxFieldType;
use SyncEngine\Form\Fields\Type\CodecFieldType;
use SyncEngine\Form\Fields\Type\CodeFieldType;
use SyncEngine\Form\Fields\Type\ColumnFieldType;
use SyncEngine\Form\Fields\Type\ConditionsFieldType;
use SyncEngine\Form\Fields\Type\EntitiesFieldType;
use SyncEngine\Form\Fields\Type\EntityFieldType;
use SyncEngine\Form\Fields\Type\GridFieldType;
use SyncEngine\Form\Fields\Type\InputFieldType;
use SyncEngine\Form\Fields\Type\MapperFieldType;
use SyncEngine\Form\Fields\Type\ParamsFieldType;
use SyncEngine\Form\Fields\Type\RadioFieldType;
use SyncEngine\Form\Fields\Type\RepeaterFieldType;
use SyncEngine\Form\Fields\Type\SchemaFieldType;
use SyncEngine\Form\Fields\Type\SecretFieldType;
use SyncEngine\Form\Fields\Type\SelectFieldType;
use SyncEngine\Form\Fields\Type\TasksFieldType;
use SyncEngine\Form\Fields\Type\TextareaFieldType;
use SyncEngine\Form\Fields\Type\ToggleFieldType;

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

		$field = match ( $config['type'] ?? '' ) {
			'textarea'       => new TextareaFieldType( $config ),
			'checkbox'       => new CheckboxFieldType( $config ),
			'toggle'         => new ToggleFieldType( $config ),
			'radio'          => new RadioFieldType( $config ),
			'select'         => new SelectFieldType( $config ),
			'entity'         => new EntityFieldType( $config ),
			'entities'       => new EntitiesFieldType( $config ),
			'grid'           => new GridFieldType( $config ),
			'mapper'         => new MapperFieldType( $config ),
			'params'         => new ParamsFieldType( $config ),
			'conditions'     => new ConditionsFieldType( $config ),
			'repeater'       => new RepeaterFieldType( $config ),
			'tasks'          => new TasksFieldType( $config ),
			'codec'          => new CodecFieldType( $config ),
			'column'         => new ColumnFieldType( $config ),
			'schema'         => new SchemaFieldType( $config ),
			'authentication' => new AuthenticationFieldType( $config ),
			'code'           => new CodeFieldType( $config ),
			'secret'         => new SecretFieldType( $config ),
			default          => new InputFieldType( $config ),
		};

		$field['type'] = $config['type'] ?? 'input';

		return $field;
	}
}
