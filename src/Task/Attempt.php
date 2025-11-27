<?php

namespace SyncEngine\Task;

use SyncEngine\Exception\ExecuteException;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\ConditionsValidator;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ExecuteData;
use SyncEngine\Service\ExecutePreviewContext;
use SyncEngine\Service\Trace\Enum\TraceLogType;
use SyncEngine\Service\Trace\TraceContext;
use SyncEngine\Service\Trace\TraceLog;
use SyncEngine\Structure\Data\ConfigData;

class Attempt extends TaskModel
{
	public function __construct()
	{
		parent::__construct();

		$this->name        = "Attempt";
		$this->description = "Try to execute tasks and catch errors to trigger other tasks";
		$this->icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M5 21q-.825 0-1.412-.587T3 19v-4h2v4h4v2zm14 0h-4v-2h4v-4h2v4q0 .825-.587 1.413T19 21M3 5q0-.825.588-1.412T5 3h4v2H5v4H3zm18 0v4h-2V5h-4V3h4q.825 0 1.413.588T21 5m-9 13q.525 0 .888-.363t.362-.887t-.363-.888T12 15.5t-.888.363t-.362.887t.363.888T12 18m-.9-3.825h1.825q0-.85.2-1.3T14 11.75q.875-.875 1.163-1.412t.287-1.288q0-1.35-.975-2.2T12 6q-1.25 0-2.15.65T8.55 8.5l1.65.675q.175-.65.663-1.062T12 7.7q.725 0 1.163.388t.437 1.037q0 .5-.237.938t-.813.912q-.825.725-1.137 1.4t-.313 1.8"/></svg>';
	}

	public function getFields(): array
	{
		return [
			'actions'    => [
				'label'    => $this->trans( 'Actions' ),
				'type'     => 'tasks',
				'required' => true,
			],
			'catch' => [
				'label'       => $this->trans( 'Catch errors' ),
				'description' => $this->trans( 'Define how to handle errors. When an error matches a condition it will prevent the error and run the defined actions instead. The error will still be logged.' ),
				'type'        => 'repeater',
				'actions'  => [
					'disable',
					'delete',
				],
				'fieldset'    => [
					'conditions' => [
						'label'    => $this->trans( 'Conditions' ),
						'description' => $this->trans( 'Leave empty to catch all errors.' ),
						'type'     => 'conditions',
						'required' => false,
						'taggable' => true,
						'source'   => [
							'placeholder' => '{{ error }}',
						],
					],
					'actions'        => [
						'label'    => $this->trans( 'Actions' ),
						'type'     => 'tasks',
						'required' => false,
					],
				],
			],
		];
	}

	public function execute( ConfigData $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		$actions = $config['actions'];

		if ( empty( $actions ) ) {
			$context->addError( $this->trans( 'No actions defined' ) );

			return $data;
		}

		$catchableContext = $this->getCatchableContext( $context );

		$traceLocation = $context->getTrace()?->getCurrentTrace()?->getTraversedNodes() ?? null;

		try {
			/**
			 * Run default actions which will throw a ExecuteException in case an action logs an error.
			 * @throws ExecuteException
			 */
			$data = $context->getExecuteService()->executeTasks( $actions, $catchableContext, ExecuteData::create( $data->normalize() ) );

		} catch ( ExecuteException $error ) {

			$trace = $error->getDebugInfo();

			if ( $trace instanceof TraceLog ) {
				$data = $this->executeCatch( $config, $context, $data, $trace );
			} else {
				// Invalid.
				$context->addError( $error );
			}

		} catch ( \Throwable $error ) {
			$context->addError( $error );
		}

		if ( $traceLocation ) {
			$context->getTrace()->getCurrentTrace()->restoreTraversedNodes( $traceLocation );
		}

		return $data;
	}

	public function executeCatch( ConfigData $config, ExecuteContext $context, ExecuteData $data, TraceLog $trace ): ExecuteData
	{
		$errorSource = $trace->normalize();
		$catch       = $config['catch'] ?? [];
		$conditions  = new ConditionsValidator();
		$selected    = null;

		foreach ( $catch as $index => $option ) {
			if ( ! empty( $option['_disabled'] ) ) {
				continue;
			}

			if (
				empty( $option['conditions'] )
				|| $conditions->validate(
					$context->parseTag( $option['conditions'], [ 'error' => $errorSource ] ),
					$errorSource
				)
			) {
				$catchActions = $option['actions'] ?? null;

				$selected = $this->trans(
					'Run catch {name}',
					[ 'name' => $index . ( ! empty( $option['_label'] ) ? ': ' . $option['_label'] : '' ) ]
				);
				break;
			}
		}

		if ( ! $selected ) {
			// Uncaught error.
			$context->addError( $trace );

			return $data;
		}

		// Log original error as notice.
		$trace->setType( TraceLogType::NOTICE );
		$context->addLog( $trace );

		$context->getTrace()?->enterTrace( $selected );

		if ( ! empty( $catchActions ) && is_iterable( $catchActions ) ) {
			$data = $context->getExecuteService()->executeTasks( $catchActions, $context, $data );
		}

		$context->getTrace()?->leaveTrace( $selected );

		return $data;
	}

	public function getCatchableContext( ExecuteContext $context ): ExecuteContext
	{
		if ( $context instanceof ExecutePreviewContext ) {
			return new class( $context->getExecuteService(), parent: $context ) extends ExecutePreviewContext {
				public function addError( TraceLog|\Throwable|string $message, mixed $info = null ): void
				{
					if ( $message instanceof \ErrorException ) {
						// PHP Errors should never be caught by this task.
						throw $message;
					}

					if ( $message instanceof TraceLog ) {
						$trace = $message;
					} else {
						$trace = TraceLog::create( $message, TraceLogType::ERROR, TraceContext::create( $this ) );
					}

					$message = (string) $trace->getMessage() ?? $trace->getException()?->getMessage();
					if ( $info ) {
						$trace->setInfo( $info );
					}

					throw new ExecuteException( $message, $trace, $trace->getException()?->getCode() ?? 0, $trace->getException() );
				}
			};
		}

		return new class( $context->getExecuteService(), parent: $context ) extends ExecuteContext {
			public function addError( TraceLog|\Throwable|string $message, mixed $info = null ): void
			{
				if ( $message instanceof \ErrorException ) {
					// PHP Errors should never be caught by this task.
					throw $message;
				}

				if ( $message instanceof TraceLog ) {
					$trace = $message;
				} else {
					$trace = TraceLog::create( $message, TraceLogType::ERROR, TraceContext::create( $this ) );
				}

				$message = (string) $trace->getMessage() ?? $trace->getException()?->getMessage();
				if ( $info ) {
					$trace->setInfo( $info );
				}

				throw new ExecuteException( $message, $trace, $trace->getException()?->getCode() ?? 0, $trace->getException() );
			}
		};
	}

	public function getTags(): array
	{
		return [
			'error' => '_input',
		];
	}
}
