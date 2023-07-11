<?php

namespace App\Task;

use App\Component\ExecutionContext;
use App\Model\TaskModel;

class Replace extends TaskModel
{
	public function __construct()
	{
		$this->type = 'modifier';
		$this->name = 'Replace';
		$this->description = 'Find and replace';

		parent::__construct();
	}

	public function getFields(): array {
		return [
			'action' => [
				'label'   => 'Action',
				'type'    => 'select',
				'default' => 'value',
				'choices' => [
					'value' => 'Replace values',
					'key'   => 'Replace keys',
					'both' => 'Replace keys and values',
				],
			],
			'key' => [
				'label' => 'Key',
				'description' => 'The key for the value that needs to be replaced',
				'type' => 'text',
				'conditionals' => [
					'action' => 'value',
				],
			],
			'recursive' => [
				'label' => 'Replace recursively?',
				'type' => 'boolean',
			],
			'params' => [
				'label' => '',
				'type'  => 'columns',
				'columns' => [
					'find'    => 'Find',
					'replace' => 'Replace',
				]
			],
		];
	}

	function execute( array $config, ExecutionContext $context, $data )
	{
		$mapper = [];
		foreach ( $config['params'] as $map ) {
			if ( ! isset( $map['find'] ) && ! isset( $map['replace'] ) ) {
				continue;
			}
			$mapper[ $map['find'] ] = $map[ 'replace' ];
		}
		$action = $config['action'] ?? 'value';

		$replaced = [];
		foreach ( $data as $key => $value ) {

			if ( ! empty( $config[ 'key' ] ) && $config[ 'key' ] !== $key ) {
				$replaced[ $key ] = $value;
				continue;
			}

			foreach ( $mapper as $find => $replace ) {

				if ( 'key' === $action || 'both' === $action ) {
					$key = str_replace( $find, $replace, $key );
				}

				if ( 'value' === $action || 'both' === $action ) {
					if ( is_array( $value ) ) {
						if ( ! empty( $config['recursive'] ) ) {
							$value = $this->execute( $config, $context, $value );
						}
					} elseif ( is_string( $value ) ) {
						$value = str_replace( $find, $replace, $value );
					} elseif ( is_float( $value ) ) {
						$value = (float) str_replace( $find, $replace, $value );
					} elseif ( is_int( $value ) ) {
						$value = (int) str_replace( $find, $replace, $value );
					}
					// Other types not supported.
				}

				$replaced[ $key ] = $value;

			}
		}

		return $replaced;
	}
}
