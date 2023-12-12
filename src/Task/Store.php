<?php

namespace SyncEngine\Task;

use SyncEngine\Model\DatasetModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\ResourceData;

class Store extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'storage';
		$this->name        = $this->trans( 'Store' );
		$this->description = $this->trans( 'Get or set a dataset' );
	}

	public function getFields(): array
	{
		return [
			'action'    => [
				'label'    => $this->trans( 'Action' ),
				'type'     => 'select',
				'default'  => 'set',
				'required' => true,
				'choices'  => [
					'set' => $this->trans( 'Set dataset' ),
					'get' => $this->trans( 'Get dataset' ),
				],
			],
			'dataset'   => [
				'label'    => $this->trans( 'Dataset' ),
				'type'     => 'entity',
				'entity'   => 'dataset',
				'actions'  => [ 'edit', 'create' ],
				'required' => true,
			],
			'key'       => [
				'label'    => $this->trans( 'Current data column key' ),
				'help'     => $this->trans( 'Nested keys are supported: key.nested_key' ),
				'type'     => 'text', // @todo Column/Key selection field type.
				'taggable' => true,
			],
			'path'      => [
				'label'    => $this->trans( 'Dataset column key/path' ),
				'help'     => $this->trans( 'Set the path where this value will be stored or leave empty. Use dots (.) to traverse into the dataset.' ),
				'type'     => 'text',
				'taggable' => true,
			],
			'not_found' => [
				'label'        => $this->trans( 'Not found action' ),
				'help'         => $this->trans( 'Action if the tag is not found' ),
				'type'         => 'select',
				'default'      => 'skip',
				'choices'      => [
					'override' => $this->trans( 'Override with empty value' ),
					'skip'     => $this->trans( 'Skip task' ),
				],
				'conditionals' => [ 'action' => 'get' ],
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['dataset'] ) ) {
			$context->addError( $this->trans( 'No dataset selected' ) );

			return $data;
		}

		$dataset = DatasetModel::get( $config['dataset'] );

		if ( ! $dataset ) {
			$context->addError( $this->trans( 'Dataset not found' ) );

			return $data;
		}

		$key       = $config['key'] ?? '';
		$action    = $config['action'] ?? false;
		$path      = $config['path'] ?? false;
		$not_found = $config['not_found'] ?? '';

		if ( 'get' === $action ) {
			if ( $path ) {
				$value = $dataset->getData( $path );
			} else {
				$value = $dataset->getData();
			}

			if ( $value || 'override' === $not_found ) {
				if ( $key ) {
					$data->set( $value, $key );
				} else {
					$data = new ExecuteData( $value );
				}
			}
		} else {
			$value = $data->get( $key ?? null );

			if ( null === $value ) {
				if ( 'override' !== $not_found ) {
					return $data;
				}
				if ( 'format' !== $dataset->getType() ) {
					$data = [];
				}
			}

			if ( ! is_array( $value ) && ! $path ) {
				// Enforce format type since it's not an array.
				$dataset->setType( 'format' );
				$dataset->setData( [ 'value' => $value ] );
			} else {
				if ( $path ) {
					$dataset->setData( $value, $path );
				} else {
					$dataset->setData( $value );
				}
			}

			$dataset->persist(  true );
		}

		return $data;
	}
}
