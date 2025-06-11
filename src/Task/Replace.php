<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Structure\Data\ConfigData;
use SyncEngine\Task\Type\ModifierTaskType;

class Replace extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = ModifierTaskType::TYPE;
		$this->icon        = 'task-replace';
		$this->name        = $this->trans( 'Replace' );
		$this->description = $this->trans( 'Find and replace' );
	}

	public function getFields(): array
	{
		return [
			'key'       => [
				'label'       => $this->trans( 'Key / Column name' ),
				'help'        => [
					$this->trans( 'The data column name for the values that need to be replaced' ),
					$this->trans( 'In case the value is a list of values this task will iterate the replacements for all' ),
					$this->trans( 'Nested keys are supported: {example}', [ 'example' => 'key.nested_key', ] ),
					$this->trans( 'Leave empty for root' ),
				],
				'type'        => 'text',
				'taggable'    => true,
			],
			'action'    => [
				'label'    => $this->trans( 'Action' ),
				'type'     => 'select',
				'default'  => 'value',
				'required' => true,
				'choices'  => [
					'value' => $this->trans( 'Replace values' ),
					'key'   => $this->trans( 'Replace keys' ),
					'both'  => $this->trans( 'Replace keys and values' ),
				],
			],
			'recursive' => [
				'label' => $this->trans( 'Replace recursively?' ),
				'type'  => 'switch',
			],
			'params'    => [
				'type'     => 'grid',
				'taggable' => true,
				'sortable' => true,
				'columns'  => [
					'find'    => $this->trans('Find'),
					'replace' => $this->trans('Replace'),
				],
			],
		];
	}

	public function execute( ConfigData $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		if ( ! is_iterable( $data ) ) {
			$context->addError( $this->trans( 'Data not iterable' ) );

			return $data;
		}

		$params = [];
		foreach ( $config['params'] as $map ) {
			if ( ! isset( $map['find'] ) ) {
				if ( ! isset( $map['replace'] ) ) {
					continue;
				}

				$context->addError( $this->trans( 'Find value not set' ) );

				return $data;
			}

			$params[ $map['find'] ] = $map['replace'] ?? '';
		}

		$action = $config['action'] ?? 'value';
		$recursive = ! empty( $config['recursive'] );

		$key = $config['key'] ?? null;
		$item = $data->get( $key );

		if ( ! is_iterable( $item ) ) {
			if ( 'value' !== $action ) {
				$context->addError( $this->trans( 'Cannot replace scalar data' ) );

				return $data;
			}

			foreach ( $params as $find => $replace ) {
				$item = $this->replaceValue( $find, $replace, $item );
			}

			$data->set( $item, $key );
		} else {
			$replacements = $this->replaceData( $params, $action, $recursive, $item );

			$data->set( $replacements, $key );
		}

		return $data;
	}

	protected function replaceData( array $params, string $action, bool $recursive, iterable $data ): array
	{
		$replaced = [];
		foreach ( $data as $key => $value ) {

			if ( is_iterable( $value ) && $recursive ) {
				$value = $this->replaceData( $params, $action, true, $value );
			}

			foreach ( $params as $find => $replace ) {

				if ( 'key' === $action || 'both' === $action ) {
					$key = str_replace( $find, $replace, (string) $key );
				}

				if ( 'value' === $action || 'both' === $action ) {
					$value = $this->replaceValue( $find, $replace, $value );
				}

				$replaced[ $key ] = $value;
			}
		}

		return $replaced;
	}

	protected function replaceValue( $find, $replace, $value )
	{
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

		return $value;
	}
}
