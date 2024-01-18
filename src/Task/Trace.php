<?php

namespace SyncEngine\Task;

use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutionContext;

class Trace extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->type        = 'utility';
		$this->name        = $this->trans( 'Trace' );
		$this->description = $this->trans( 'Trace current status with a log or error.' );
	}

	public function getFields(): array
	{
		return [
			'trace' => [
				'label'       => $this->trans( 'Trace type' ),
				'description' => $this->trans( 'Creating an error will result in the trace reporting as failed.' ),
				'type'        => 'select',
				'required'    => true,
				'choices'     => [
					'log'   => $this->trans( 'Log message' ),
					'error' => $this->trans( 'Error message' ),
				],
			],
			'message' => [
				'label' => $this->trans( 'Message' ),
				'type' => 'text',
				'taggable' => true,
				'required'    => true,
			]
		];
	}

	public function execute( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		if ( empty( $config['message'] ) ) {
			$context->addError( 'Message not configured' );

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
