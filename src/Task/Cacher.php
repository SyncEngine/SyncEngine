<?php

namespace App\Task;

use App\Component\AutomationContext;
use App\Model\TaskModel;

class Cacher extends TaskModel
{
	public function __construct()
	{
		$this->type = 'cacher';
		$this->name = 'Cacher';
		$this->description = 'Get or set a value in the context cache';

		parent::__construct();
	}

	public function getFields(): array {
		return [
			'action' => [
				'label' => 'Action',
				'type' => 'select',
				'default' => 'set',
				'choices' => [
					'set' => 'Set cache',
					'get' => 'Get cache',
				]
			],
			'key' => [
				'label' => 'Data key',
				'type' => 'text', // @todo Column/Key selection field type.
			],
			'ref' => [
				'label' => 'Cache reference',
				'help' => 'Can be used like: {{ context.cache.ref }}',
				'type' => 'text',
			],
		];
	}

	function execute( array $config, AutomationContext $context, $data )
	{
		$key = $config['key'] ?? '';
		$ref = $config['ref'];
		$action = $config['$action'] ?? false;

		if ( ! $ref ) {
			// @todo error.
		}

		if ( 'get' === $action ) {
			$value = $context->getContextCache( $ref );
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
			$context->setContextCache( $ref, $value );
		}

		return $data;
	}
}
