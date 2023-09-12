<?php

namespace App\Task;

use App\Model\TaskModel;
use App\Service\ExecutionContext;

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
				'label' => 'Data key',
				'type'  => 'text', // @todo Column/Key selection field type.
			],
			'tag'    => [
				'label' => 'Cache tag reference',
				'help'  => 'Can be used like: {{ context.cache.REFERENCE }}',
				'type'  => 'text',
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
	{
		$key    = $config['key'] ?? '';
		$tag    = $config['tag'];
		$action = $config['action'] ?? false;

		if ( ! $tag ) {
			$context->addError( 'Cache tag reference missing' );
			return $data;
		}

		if ( 'get' === $action ) {
			$value = $context->getContextCache( $tag );
			if ( $key ) {
				$data[ $key ] = $value;
			} else {
				$data = $value;
			}
		} else {
			$value = $data;
			if ( $key ) {
				$value = $value[ $key ] ?? null;
			}
			$context->setContextCache( $tag, $value );
		}

		return $data;
	}
}
