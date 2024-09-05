<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ResourceData;
use SyncEngine\Task\Type\StorageTaskType;

class Cache extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = StorageTaskType::TYPE;
		$this->name        = $this->trans( 'Cache' );
		$this->description = $this->trans( 'Get or set a value in the context cache' );
	}

	public function getFields(): array
	{
		return [
			'key'       => [
				'label'       => $this->trans( 'Key / Column name' ),
				'type'        => 'text', // @todo Column/Key selection field type.
				'help'        => [
					$this->trans( 'Nested keys are supported: key.nested_key' ),
					$this->trans( 'Leave empty for root' ),
				],
				'taggable'    => true,
				'conditions' => [ 'action' => [ 'operator' => '!=', 'compare' => 'clear' ] ],
			],
			'action'    => [
				'label'    => $this->trans( 'Action' ),
				'type'     => 'select',
				'default'  => 'set',
				'required' => true,
				'choices'  => [
					'set'   => $this->trans( 'Set cache' ),
					'get'   => $this->trans( 'Get cache' ),
					'clear' => $this->trans( 'Clear cache' ),
				],
			],
			'tag'       => [
				'label'    => $this->trans( 'Cache tag reference/path' ),
				'help'     => $this->trans( 'Can be used like: {tag}', [ 'tag' => '{{ context.cache.REFERENCE }}' ] ),
				'type'     => 'text',
				'taggable' => true,
				'required' => true,
			],
			'method'    => [
				'label'      => $this->trans( 'Method' ),
				'type'       => 'select',
				'default'    => 'replace',
				'choices'    => [
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
		$key       = $config['key'] ?? null;
		$tag       = $config['tag'] ?? null;
		$action    = $config['action'] ?? 'set';
		$not_found = $config['not_found'] ?? '';

		if ( ! $tag ) {
			$context->addError( $this->trans( 'Cache tag reference missing' ) );

			return $data;
		}

		switch ( $action ) {
			case 'get':
				$value = $context->getCacheTag( $tag );
				if ( $value || 'override' === $not_found ) {
					if ( $key ) {
						$data->set( $value, $key );
					} else {
						$data = new ExecuteData( $value );
					}
				}
			break;
			case 'set':
				$value = $data->get( $key );

				switch ( $config['method'] ?? '' ) {
					case 'append':
						$resource = new ResourceData( (array) $context->getCacheTag( $tag ) );
						$resource->append( $value );

						$context->setCacheTag( $tag, $resource->get() );
					break;
					case 'merge':
						$resource = new ResourceData( (array) $context->getCacheTag( $tag ) );
						$resource->merge( $value );

						$context->setCacheTag( $tag, $resource->get() );
					break;
					default:
						$context->setCacheTag( $tag, $value );
					break;
				}
			break;
			case 'clear':
				$context->setCacheTag( $tag, null );
			break;
		}

		return $data;
	}
}
