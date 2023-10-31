<?php

namespace App\Task;

use App\Model\TaskModel;
use App\Service\ExecutionContext;
use App\Service\ResourceData;

class Cache extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'storage';
		$this->name        = 'Cache';
		$this->description = 'Get or set a value in the context cache';

		parent::__construct();
	}

	public function getFields(): array
	{
		return [
			'action' => [
				'label'   => 'Action',
				'type'    => 'select',
				'default' => 'set',
				'choices' => [
					'set' => 'Set cache',
					'get' => 'Get cache',
				],
			],
			'key'    => [
				'label'    => 'Data key',
				'type'     => 'text', // @todo Column/Key selection field type.
				'taggable' => true,
			],
			'tag'    => [
				'label' => 'Cache tag reference',
				'help'  => 'Can be used like: {{ context.cache.REFERENCE }}',
				'type'  => 'text',
			],
			'not_found' => [
				'label'   => 'Not found action',
				'help'    => 'Action if the tag is not found',
				'type'    => 'select',
				'default' => 'skip',
				'choices' => [
					'override' => 'Override with empty value',
					'skip'     => 'Skip task',
				],
				'conditionals' => [ 'action' => 'get' ],
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
	{
		$key       = $config['key'] ?? '';
		$tag       = $config['tag'] ?? null;
		$action    = $config['action'] ?? null;
		$not_found = $config['not_found'] ?? '';

		if ( ! $tag ) {
			$context->addError( 'Cache tag reference missing' );
			return $data;
		}

		if ( 'get' === $action ) {
			$value = $context->getContextCache( $tag );
			if ( $value || 'override' === $not_found ) {
				if ( $key ) {
					$data = ( new ResourceData( $data ) )->set( $value, $key )->get();
				} else {
					$data = $value;
				}
			}
		} else {
			$value = $data;
			if ( $key ) {
				$value = ( new ResourceData( $data ) )->get( $key );
			}
			$context->setContextCache( $tag, $value );
		}

		return $data;
	}
}
