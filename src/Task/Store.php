<?php

namespace SyncEngine\Task;

use SyncEngine\Model\StorageModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Service\ResourceData;
use SyncEngine\Task\Type\StorageTaskType;

class Store extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = StorageTaskType::TYPE;
		$this->name        = $this->trans( 'Store',[],"task/store" );
		$this->description = $this->trans( 'Get or set a storage',[],"task/store" );
	}

	public function getFields(): array
	{
		return [
			'action'    => [
				'label'    => $this->trans( 'Action',[],"task/store" ),
				'type'     => 'select',
				'default'  => 'set',
				'required' => true,
				'choices'  => [
					'set' => $this->trans( 'Set storage',[],"task/store" ),
					'get' => $this->trans( 'Get storage',[],"task/store" ),
				],
			],
			'storage'   => [
				'label'    => $this->trans( 'Storage',[],"task/store" ),
				'type'     => 'entity',
				'entity'   => 'storage',
				'actions'  => [ 'edit', 'create' ],
				'required' => true,
			],
			'key'       => [
				'label'    => $this->trans( 'Current data column name/key',[],"task/store" ),
				'help'     => $this->trans( 'Nested keys are supported: key.nested_key',[],"task/store" ),
				'type'     => 'text', // @todo Column/Key selection field type.
				'taggable' => true,
			],
			'path'      => [
				'label'    => $this->trans( 'Storage column key/path',[],"task/store" ),
				'help'     => $this->trans( 'Set the path where this value will be stored or leave empty. Use dots (.) to traverse into the storage.',[],"task/store" ),
				'type'     => 'text',
				'taggable' => true,
			],
			'method'    => [
				'label'    => $this->trans( 'Method',[],"task/store" ),
				'type'     => 'select',
				'default'  => 'replace',
				'choices'  => [
					'replace' => $this->trans( 'Replace',[],"task/store" ),
					'merge'   => $this->trans( 'Merge',[],"task/store" ),
					'append'  => $this->trans( 'Append',[],"task/store" ),
				],
				'conditions' => [ 'action' => 'set' ],
			],
			'not_found' => [
				'label'      => $this->trans( 'Not found action',[],"task/store" ),
				'help'       => $this->trans( 'Action if the tag is not found',[],"task/store" ),
				'type'       => 'select',
				'default'    => 'skip',
				'choices'    => [
					'override' => $this->trans( 'Override with empty value',[],"task/store" ),
					'skip'     => $this->trans( 'Skip task',[],"task/store" ),
				],
				'conditions' => [ 'action' => 'get' ],
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['storage'] ) ) {
			$context->addError( $this->trans( 'No storage selected',[],"task/store" ) );

			return $data;
		}

		$storage = StorageModel::get( $config['storage'] );

		if ( ! $storage ) {
			$context->addError( $this->trans( 'Storage not found',[],"task/store" ) );

			return $data;
		}

		$key       = $config['key'] ?? '';
		$action    = $config['action'] ?? false;
		$path      = $config['path'] ?? null;
		$not_found = $config['not_found'] ?? '';

		if ( 'get' === $action ) {
			if ( $path ) {
				if ( 'format' === $storage->getType() ) {
					$context->addError( $this->trans( 'Formatted storage cannot contain paths',[],"task/store" ) );
				}
				$value = $storage->getData( $path );
			} else {
				$value = $storage->getData();
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
					return $data; // @todo Enable not found for setter, currently disabled.
				}
				if ( 'format' !== $storage->getType() ) {
					$data = [];
				}
			}

			if ( ! is_array( $value ) && ! $path ) {
				// Enforce format type since it's not an array.
				$storage->setType( 'format' );
				$storage->setData( [ 'value' => $value ] );
			} else {
				if ( 'format' === $storage->getType() ) {
					$context->addError( $this->trans( 'This is a formatted storage, please select a different storage or change the storage type',[],"task/store" ) );
				}

				switch ( $config['method'] ?? '' ) {
					case 'append':
						$resource = new ResourceData( $storage->getData( $path, [] ) );
						$resource->append( $value );

						$storage->setData( $resource->get(), $path );
					break;
					case 'merge':
						$resource = new ResourceData( $storage->getData( $path, [] ) );
						$resource->merge( $value );

						$storage->setData( $resource->get(), $path );
					break;
					default:
						$storage->setData( $value, $path );
					break;
				}
			}

			$storage->persist(  true );
		}

		return $data;
	}
}
