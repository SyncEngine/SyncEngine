<?php

namespace App\Task;

use App\Model\TaskModel;
use App\Service\ExecutionContext;

class Replace extends TaskModel
{
	public function __construct()
	{
		$this->type        = 'modifier';
		$this->name        = 'Replace';
		$this->description = 'Find and replace';

		parent::__construct();
	}

	public function getFields(): array
	{
		return [
			'action'    => [
				'label'   => 'Action',
				'type'    => 'select',
				'default' => 'value',
				'choices' => [
					'value' => 'Replace values',
					'key'   => 'Replace keys',
					'both'  => 'Replace keys and values',
				],
			],
			'key'       => [
				'label'        => 'Key',
				'description'  => 'The key for the value that needs to be replaced',
				'type'         => 'text',
				'taggable'     => true,
				'conditionals' => [
					'action' => 'value',
				],
			],
			'recursive' => [
				'label' => 'Replace recursively?',
				'type'  => 'boolean',
			],
			'params'    => [
				'label'    => '',
				'type'     => 'columns',
				'taggable' => true,
				'columns'  => [
					'find'    => 'Find',
					'replace' => 'Replace',
				],
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, $data ): array
	{
		if ( ! is_iterable( $data ) ) {
			throw new \Exception( 'Data not iterable' );
		}

		$params = [];
		foreach ( $config['params'] as $map ) {
			if ( ! isset( $map['find'] ) && ! isset( $map['replace'] ) ) {
				continue;
			}
			$params[ $map['find'] ] = $map['replace'];
		}

		$action = $config['action'] ?? 'value';
		$recursive = ! empty( $config['recursive'] );

		return $this->_execute( $params, $action, $recursive, $data );
	}

	protected function _execute( array $params, string $action, bool $recursive, iterable $data ): array
	{
		$replaced = [];
		foreach ( $data as $key => $value ) {

			if ( ! empty( $config['key'] ) && $config['key'] !== $key ) {
				$replaced[ $key ] = $value;
				continue;
			}

			foreach ( $params as $find => $replace ) {

				if ( 'key' === $action || 'both' === $action ) {
					$key = str_replace( $find, $replace, $key );
				}

				if ( 'value' === $action || 'both' === $action ) {
					if ( is_string( $value ) ) {
						$value = str_replace( $find, $replace, $value );
					} elseif ( is_float( $value ) ) {
						$value = (float) str_replace( $find, $replace, $value );
					} elseif ( is_int( $value ) ) {
						$value = (int) str_replace( $find, $replace, $value );
					}
					// Other types not supported.
				}

				if ( is_iterable( $value ) && $recursive ) {
					$value = $this->_execute( $params, $action, true, $value );
				}

				$replaced[ $key ] = $value;
			}
		}

		return $replaced;
	}
}
