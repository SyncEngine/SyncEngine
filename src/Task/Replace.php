<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Task\Type\ModifierTaskType;

class Replace extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = ModifierTaskType::TYPE;
		$this->name        = $this->trans( 'Replace',[],"task/replace" );
		$this->description = $this->trans( 'Find and replace',[],"task/replace" );
	}

	public function getFields(): array
	{
		return [
			'action'    => [
				'label'    => $this->trans( 'Action',[],"task/replace" ),
				'type'     => 'select',
				'default'  => 'value',
				'required' => true,
				'choices'  => [
					'value' => $this->trans( 'Replace values',[],"task/replace" ),
					'key'   => $this->trans( 'Replace keys',[],"task/replace" ),
					'both'  => $this->trans( 'Replace keys and values',[],"task/replace" ),
				],
			],
			'key'       => [
				'label'       => $this->trans( 'Key',[],"task/replace" ),
				'description' => $this->trans( 'The key for the value that needs to be replaced',[],"task/replace" ),
				'type'        => 'text',
				'taggable'    => true,
				'conditions'  => [
					'action' => 'value',
				],
			],
			'recursive' => [
				'label' => $this->trans( 'Replace recursively?',[],"task/replace" ),
				'type'  => 'boolean',
			],
			'params'    => [
				'type'     => 'grid',
				'taggable' => true,
				'columns'  => [
					'find'    => $this->trans('Find',[],"task/replace"),
					'replace' => $this->trans('Replace',[],"task/replace"),
				],
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		if ( ! is_iterable( $data ) ) {
			$context->addError( $this->trans( 'Data not iterable',[],"task/replace" ) );

			return $data;
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

		return new ExecuteData( $this->_execute( $params, $action, $recursive, $config, $data ) );
	}

	protected function _execute( array $params, string $action, bool $recursive, array $config, iterable $data ): array
	{
		$replaced = [];
		foreach ( $data as $key => $value ) {

			// @todo Use ResourceData?
			if ( ! empty( $config['key'] ) && $config['key'] !== $key ) {
				$replaced[ $key ] = $value;
				continue;
			}

			if ( is_iterable( $value ) && $recursive ) {
				$value = $this->_execute( $params, $action, true, $config, $value );
			}

			foreach ( $params as $find => $replace ) {

				if ( 'key' === $action || 'both' === $action ) {
					$key = str_replace( $find, $replace, (string) $key );
				}

				if ( 'value' === $action || 'both' === $action ) {
					if ( is_string( $value ) ) {
						$value = str_replace( $find, $replace, $value );
					} elseif ( is_float( $value ) ) {
						if ( is_numeric( $replace ) ) {
							// Keep type.
							$value = (float) str_replace( $find, $replace, (string) $value );
						} else {
							$value = str_replace( $find, $replace, (string) $value );
						}
					} elseif ( is_int( $value ) ) {
						if ( is_numeric( $replace ) ) {
							// Keep type.
							$value = (int) str_replace( $find, $replace, (string) $value );
						} else {
							$value = str_replace( $find, $replace, (string) $value );
						}
					}
					// Iterable already done and other types not supported.
				}

				$replaced[ $key ] = $value;
			}
		}

		return $replaced;
	}
}
