<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Task\Type\StructureTaskType;

class Group extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = StructureTaskType::TYPE;
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
					'key'  => $this->trans( 'Group by key' ),
					'assoc' => $this->trans( 'Associative indexes' ),
				],
			],*/
			'group_by' => [
				'label'       => $this->trans( 'Group by' ),
				'description' => $this->trans( 'Set column name/key to group by value' ),
				'type'        => 'text',
				'default'     => '',
			],
			'default_group' => [
				'label'       => $this->trans( 'Default group key' ),
				'description' => $this->trans( 'Will be used when the row does not include the defined group column. If not set, the row will be removed.' ),
				'type'        => 'text',
				'default'     => '',
			],
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['group_by'] ) ) {
			$context->addError( $this->trans( 'No group column configured' ) );

			return $data;
		}

		$group_by      = $config['group_by'];
		$default_group = $config['default_group'] ?? null;

		$new = [];
		foreach ( $data as $key => $value ) {
			$group = $data->get( $key . '.' . $group_by, $default_group );

			if ( $group ) {
				$new[ $group ][ $key ] = $value;
			}
		}

		return new ExecuteData( $new );
	}
}
