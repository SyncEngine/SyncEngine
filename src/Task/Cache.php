<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Task\Type\StorageTaskType;

class Cache extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = StorageTaskType::TYPE;
		$this->name        = $this->trans( 'Cache',[],"task/cache" );
		$this->description = $this->trans( 'Get or set a value in the context cache',[],"task/cache" );
	}

	public function getFields(): array
	{
		return [
			'action'    => [
				'label'    => $this->trans( 'Action',[],"task/cache" ),
				'type'     => 'select',
				'default'  => 'set',
				'required' => true,
				'choices'  => [
					'set' => $this->trans( 'Set cache',[],"task/cache" ),
					'get' => $this->trans( 'Get cache',[],"task/cache" ),
				],
			],
			'key'       => [
				'label'    => $this->trans( 'Current data column name/key',[],"task/cache" ),
				'type'     => 'text', // @todo Column/Key selection field type.
				'help'     => $this->trans( 'Nested keys are supported: key.nested_key',[],"task/cache" ),
				'taggable' => true,
			],
			'tag'       => [
				'label'    => $this->trans( 'Cache tag reference/path',[],"task/cache" ),
				'help'     => $this->trans( 'Can be used like: {{ context.cache.REFERENCE }}' ),
				'type'     => 'text',
				'required' => true,
			],
			'not_found' => [
				'label'      => $this->trans( 'Not found action',[],"task/cache" ),
				'help'       => $this->trans( 'Action if the tag is not found',[],"task/cache" ),
				'type'       => 'select',
				'default'    => 'skip',
				'choices'    => [
					'override' => $this->trans( 'Override with empty value',[],"task/cache" ),
					'skip'     => $this->trans( 'Skip task',[],"task/cache" ),
				],
				'conditions' => [ 'action' => 'get' ],
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$key       = $config['key'] ?? null;
		$tag       = $config['tag'] ?? null;
		$action    = $config['action'] ?? null;
		$not_found = $config['not_found'] ?? '';

		if ( ! $tag ) {
			$context->addError( $this->trans( 'Cache tag reference missing',[],"task/cache" ) );

			return $data;
		}

		if ( 'get' === $action ) {
			$value = $context->getCacheTag( $tag );
			if ( $value || 'override' === $not_found ) {
				if ( $key ) {
					$data->set( $value, $key );
				} else {
					$data = new ExecuteData( $value );
				}
			}
		} else {
			$context->setCacheTag( $tag, $data->get( $key ) );
		}

		return $data;
	}
}
