<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\Conditions;
use SyncEngine\Service\Data\ResourceData;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Task\Type\StructureTaskType;

class Group extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = StructureTaskType::TYPE;
		$this->icon        = 'task-group';
		$this->name        = $this->trans( 'Group' );
		$this->description = $this->trans( 'Group your data' );
	}

	public function getFields(): array
	{
		return [
			/*'method'       => [
				'label'    => $this->trans( 'Method' ),
				'type'     => 'select',
				'default'  => '',
				'required' => true,
				'choices'  => [
					'column' => $this->trans( 'Group by column name' ),
					'assoc'  => $this->trans( 'Associative indexes' ),
				],
			],*/
			'key'      => [
				'label'       => $this->trans( 'Key / Column name' ),
				'type'        => 'text', // @todo Column/Key selection field type.
				'help'        => [
					$this->trans( 'The data column name to group items for' ),
					$this->trans( 'Nested keys are supported: key.nested_key' ),
					$this->trans( 'Leave empty for root' ),
				],
				'taggable'    => true,
			],
			'group_by'      => [
				'label'       => $this->trans( 'Group by' ),
				'description' => $this->trans( 'Set column name/key to group by value' ),
				'type'        => 'text',
				'default'     => '',
			],
			'default_group' => [
				'label'       => $this->trans( 'Default group key' ),
				'description' => $this->trans(
					'Will be used when the row does not include the defined group column or it is an empty value. If not set, the row will be removed.'
				),
				'type'        => 'text',
				'default'     => '',
			],
		];
	}

	public function execute( array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['group_by'] ) ) {
			$context->addError( $this->trans( 'No group column configured' ) );

			return $data;
		}

		$group_by      = $config['group_by'];
		$default_group = $config['default_group'] ?? null;

		$key   = $config['key'] ?? null;
		$items = $data->get( $key );

		if ( ! is_iterable( $items ) ) {
			$context->addLog( $this->trans( 'Data not iterable' ) );

			return $data;
		}

		$items = ResourceData::create( $items );

		$grouped = [];
		foreach ( $items as $index => $value ) {
			$group = $items->get( $index . '.' . $group_by );

			if ( Conditions::isEmptyValue( $group ) ) {
				$group = $default_group;
			}

			if ( $group ) {
				if ( ! Conditions::isValidKey( $group ) ) {
					$context->addError( $this->trans( 'Invalid group column: {value}', [ 'value' => $group ] ) );
				} else {
					$grouped[ $group ][ $index ] = $value;
				}
			}
		}

		$data->set( $grouped, $key );

		return $data;
	}
}
