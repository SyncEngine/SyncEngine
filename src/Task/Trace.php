<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;
use SyncEngine\Task\Type\UtilityTaskType;

class Trace extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = UtilityTaskType::TYPE;
		$this->name        = $this->trans( 'Trace',[],"task/trace" );
		$this->description = $this->trans( 'Trace current status with a log or error.',[],"task/trace" );
	}

	public function getFields(): array
	{
		return [
			'trace' => [
				'label'       => $this->trans( 'Trace type',[],"task/trace" ),
				'description' => $this->trans( 'Creating an error will result in the trace reporting as failed.',[],"task/trace" ),
				'type'        => 'select',
				'required'    => true,
				'choices'     => [
					'log'   => $this->trans( 'Log message',[],"task/trace" ),
					'error' => $this->trans( 'Error message',[],"task/trace" ),
				],
			],
			'message' => [
				'label' => $this->trans( 'Message',[],"task/trace" ),
				'type' => 'text',
				'taggable' => true,
				'required'    => true,
			]
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['message'] ) ) {
			$context->addError( $this->trans('Message not configured',[],"task/trace") );

			return $data;
		}

		$message = $config['message'];
		$trace   = $config['trace'] ?? 'log';

		if ( 'error' === $trace ) {
			$context->addError( $message );
		} else {
			$context->addLog( $message );
		}

		return $data;
	}
}
