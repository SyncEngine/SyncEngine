<?php

namespace SyncEngine\Task;

use SyncEngine\Form\Fields\Collection\FieldCollection;
use SyncEngine\Model\TaskModel;
use SyncEngine\Runtime\ExecuteContext;
use SyncEngine\Runtime\ExecuteData;
use SyncEngine\Structure\Data\ConfigData;
use SyncEngine\Task\Type\UtilityTaskType;

class Wait extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = UtilityTaskType::TYPE;
		$this->icon        = 'task-wait';
		$this->name        = $this->trans( 'Wait' );
		$this->description = $this->trans( 'Wait for time to pass' );
	}

	public function getFields(): FieldCollection|array
	{
		return [
			'seconds' => [
				'label'    => $this->trans( 'Seconds to wait' ),
				'type'     => 'number',
				'required' => true,
			],
		];
	}

	public function execute( ConfigData $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		$seconds = $config['seconds'] ?? 0;

		if ( is_string( $seconds ) ) {
			$seconds = (float) str_replace( ',', '.', $seconds );
		}

		sleep( $seconds );

		return $data;
	}
}
