<?php

namespace SyncEngine\Task;

use SyncEngine\Model\StorageModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\Data\ResourceData;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Task\Type\StorageTaskType;

class Store extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = StorageTaskType::TYPE;
		$this->name        = $this->trans( 'Store' );
		$this->description = $this->trans( 'Get or set a storage' );
	}

	public function getFields(): array
	{
		return [
			'key'       => [
				'label'       => $this->trans( 'Key / Column name' ),
				'type'        => 'text', // @todo Column/Key selection field type.
				'help'        => [
					$this->trans( 'Nested keys are supported: {example}', [ 'example' => 'key.nested_key', ] ),
					$this->trans( 'Leave empty for root' ),
				],
				'taggable'    => true,
			],
			'action'    => [
				'label'    => $this->trans( 'Action' ),
				'type'     => 'select',
				'default'  => 'set',
				'required' => true,
				'choices'  => [
					'set' => $this->trans( 'Set storage' ),
					'get' => $this->trans( 'Get storage' ),
				],
			],
			'storage'   => [
				'label'    => $this->trans( 'Storage' ),
				'type'     => 'entity',
				'entity'   => 'storage',
				'actions'  => [ 'edit', 'create' ],
				'required' => true,
			],
			'path'      => [
				'label'    => $this->trans( 'Storage column key/path' ),
				'help'     => $this->trans( 'Set the path where this value will be stored or leave empty. Use dots (.) to traverse into the storage.' ),
				'type'     => 'text',
				'taggable' => true,
			],
			'method'    => [
				'label'    => $this->trans( 'Method' ),
				'type'     => 'select',
				'default'  => 'replace',
				'choices'  => [
					'replace' => $this->trans( 'Replace' ),
					'merge'   => $this->trans( 'Merge' ),
					'append'  => $this->trans( 'Append' ),
				],
				'conditions' => [ 'action' => 'set' ],
			],
			'not_found' => [
				'label'      => $this->trans( 'Not found action' ),
				'help'       => $this->trans( 'Action if the tag is not found' ),
				'type'       => 'select',
				'default'    => 'skip',
				'choices'    => [
					'override' => $this->trans( 'Override with empty value' ),
					'skip'     => $this->trans( 'Skip task' ),
				],
				'conditions' => [ 'action' => 'get' ],
			],
		];
	}

	public function execute( array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['storage'] ) ) {
			$context->addError( $this->trans( 'No storage selected' ) );

			return $data;
		}

		$storage = StorageModel::get( $config['storage'] );

		if ( ! $storage ) {
			$context->addError( $this->trans( 'Storage not found' ) );

			return $data;
		}

		$key       = $config['key'] ?? '';
		$action    = $config['action'] ?? false;
		$path      = $config['path'] ?? null;
		$not_found = $config['not_found'] ?? '';

		if ( 'get' === $action ) {
			if ( $path ) {
				if ( $storage->isRaw() ) {
					$context->addError( $this->trans( 'Raw storage cannot contain paths' ) );
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
				if ( ! $storage->isRaw() ) {
					$data = [];
				}
			}

			if ( ! is_array( $value ) && ! $path ) {
				// @todo Trigger error instead of potentially losing data?
				// Enforce raw type since it's not an array.
				$storage->setType( 'raw' );
				$storage->setData( [ 'value' => $value ] );
			} else {
				if ( $storage->isRaw() ) {
					$context->addError( $this->trans( 'This is a raw storage, please select a different storage or change the storage type' ) );
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
