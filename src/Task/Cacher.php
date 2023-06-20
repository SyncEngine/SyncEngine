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
		$this->description = 'Cache a value in context to be used later';

		parent::__construct();
	}

	public function getFields(): array {
		return [
			'key' => [
				'label' => 'Select data key',
				'type' => 'text', // @todo Column/Key selection field type.
			],
			'ref' => [
				'label' => 'Cache reference',
				'help' => 'Can be used like: {{ context.cache.ref }}',
				'type' => 'text',
			],
		];
	}

	function execute( array $config, array $data, AutomationContext $context ): array
	{
		$key = $config['key'] ?? '';
		$ref = $config['ref'];

		if ( ! $ref ) {
			// @todo error.
		}

		$value = $data;
		if ( $key ) {
			$value = $value[ $key ] ?? null;
		}

		$context->setContextCache( $value );

		return $data;
	}
}
