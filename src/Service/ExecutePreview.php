<?php

namespace SyncEngine\Service;

use SyncEngine\Entity\Automation;
use SyncEngine\Entity\Flow;
use SyncEngine\Entity\Step;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\StepModel;
use Symfony\Component\HttpFoundation\Request;

class ExecutePreview extends Execute
{
	const MODE_SAFE = 'safe';
	const MODE_LIVE = 'live';

	protected array $scope;
	protected ResourceData $trace;
	protected array $testConfig;
	protected array $parsedConfig;

	public function schedule( AutomationModel $automation ): void {	/* Nope. */ }

	public function preview( Request $request ): array
	{
		$context = new ExecutionContext( $this );
		$action  = $request->get( 'action' );

		$mode = $request->get( 'mode' ) ?? self::MODE_SAFE;
		$context->setPreviewMode( $mode );

		$ref    = $request->get( 'ref' );
		$data   = json_decode( $request->get( 'data' ), true );
		$config = json_decode( $request->get( 'config' ), true );

		$this->testConfig = $config;

		$scope = $request->get( 'scope' );
		if ( $scope ) {
			try {
				$data = $this->executeScope( json_decode( $scope, true ), $context, $data ?? [] );
			} catch ( \Throwable $e ) {
				$context->addError( $e );
			}
		}

		$data = $data instanceof ExecuteData ? $data : new ExecuteData( $data );

		if ( ! $context->getErrors() ) {
			$this->logTrace( 'Starting preview', $context, null, $data );
			try {
				switch ( $request->get( 'type' ) ) {
					case 'task':
						unset( $config['_disabled'] ); // In preview mode the final task should always be enabled.
						$result = $this->executeTask( $config, $context, $data );
					break;
					case 'step':
						$step = StepModel::create();
						$step->setConfig( $config );

						$result = $this->executeStep( $step, $context, $data );
					break;
					case 'flow':
						$flow = FlowModel::create();
						$flow->setConfig( $config );

						$result = $this->executeFlow( $flow, $context, $data );
					break;
					case 'automation':
						$automation = AutomationModel::create();
						$automation->setConfig( $config );

						$result = $this->execute( $automation, $context, $data );
					break;
					default:
						$context->addError( 'No preview type set' );
					break;
				}
			} catch ( \Throwable $e ) {
				$context->addError( $e );
			}
		}

		$return = [];

		$errors = $context->getErrors();
		if ( $errors ) {
			$return['Errors'] = $errors;
		} else {
			$return['Return'] = $result ?? [];
			$return['Info'] = [
				'count' => count( is_countable( $result ) ? $result : [] ),
			];
		}

		$logs = $context->getLogs();
		if ( $logs ) {
			$return['Logs'] = $logs;
		}

		if ( $scope && isset( $this->trace ) ) {
			$return['Trace'] = $this->trace->get();
		}

		$return['Config'] = $this->testConfig;
		$return['Parsed'] = $this->parsedConfig ?? [];

		$params = $request->request->all();
		foreach ( $params as &$param ) {
			try {
				$param = json_decode( $param, true );
			} catch ( \Throwable $e ) {
				// Nope.
			}
		}
		$return['Params'] = $params;

		$return = [
			'success' => empty( $errors ),
			'data'    => $return,
		];

		if ( $scope ) {
			$return['source'] = $data ? $data->get() : [];
		}

		return $return;
	}

	public function isCurrentScope( $item, ExecutionContext $context ): bool
	{
		if ( empty( $this->scope ) ) {
			return false;
		}

		$queued = $this->scope['queue'][ $this->scope['current'] ] ?? null;

		if ( is_object( $item ) && $queued ) {
			if ( $item::class !== $queued['_instance']::class ) {
				return false;
			}
			if ( $item->getId() !== $queued['_instance']->getId() ) {
				return false;
			}

			if ( $queued['config'] ) {
				// Override config from scope.
				$item->setConfig( $queued['config'] );
			}

			// Same item, next scope queue.
			$this->scope['current'] += 1;

			// Last queue item.
			return ( $this->scope['current'] > count( $this->scope['queue'] ) );
		}

		if ( is_array( $item ) && isset( $item['_ref'] ) ) {
			// Tasks.
			if ( $this->scope['current'] >= count( $this->scope['queue'] ) ) {
				return ( $item['_ref'] === $this->testConfig['_ref'] );
			}
		}

		return false;
	}

	public function executeScope( array $scope, ExecutionContext $context, array $data = null ): ExecuteData
	{
		$this->scope = [
			'queue' => [],
		];

		foreach ( $scope as $key => $entity ) {
			switch ( $entity['_entity'] ) {
				case 'Automation':
					$entity['_instance'] = AutomationModel::get( $entity['id'] );
				break;
				case 'Flow':
					$entity['_instance'] = FlowModel::get( $entity['id'] );
				break;
				case 'Step':
					$entity['_instance'] = StepModel::get( $entity['id'] );
				break;
				default:
					throw new \Exception( 'Invalid scope' );
			}

			// Set test config.
			if ( ! empty( $entity['config'] ) ) {
				$entity['_instance']->setConfig( $entity['config'] );
			}

			$this->scope['queue'][ $key ] = $entity;
		}

		$startEntity = $this->scope['queue'][0]['_instance'];

		$this->scope['current'] = 1; // First in queue.

		try {
			switch ( true ) {
				case $startEntity instanceof AutomationModel:
					$data = $this->execute( $startEntity, $context, $data );
				break;
				case $startEntity instanceof FlowModel:
					$data = $this->executeFlow( $startEntity, $context, new ExecuteData( $data ?? [] ) );
				break;
				case $startEntity instanceof StepModel:
					$data = $this->executeStep( $startEntity, $context, new ExecuteData( $data ?? [] ) );
				break;
			}
		} catch ( \Throwable $e ) {
			if ( ! isset( $e::$SYNCENGINE_EXITPREVIEW ) ) {
				$this->logTrace( 'Scope errored', $context );
				throw $e;
			}

			$data = $e->getData();
		}

		$this->logTrace( 'Scope executed', $context );
		$this->scope = [];

		return $data;
	}

	public function execute( AutomationModel $automation, ExecutionContext $context, $data = null ): array
	{
		$this->logTrace( 'Start Automation', $context, $automation, $data );

		$automation->endIterator();

		// Start new iteration. Will set to 1 if it's a new loop.
		$automation->nextIteration();

		try {
			$data = $this->fetch( $automation, $context, $data );
			$this->logTrace( 'Fetched Automation data', $context );
		} catch ( \Throwable $e ) {
			if ( isset( $e::$SYNCENGINE_EXITPREVIEW ) ) {
				throw $e; // Exit scope.
			}

			$automation->endIterator();
			$context->addError( $e );
		}

		$return = [];

		if ( $data instanceof ExecuteData ) {

			$actions = $automation->getActions();
			if ( $actions ) {
				try {
					$return = $this->executeTasks( $actions, $context, $data );
				} catch ( \Throwable $e ) {
					if ( isset( $e::$SYNCENGINE_EXITPREVIEW ) ) {
						throw $e; // Exit scope.
					}

					$automation->endIterator();
					$context->addError( $e );
				}
			}

		} else {
			$context->addError( 'No data found' );
		}

		$automation->endIterator();

		return $return instanceof ExecuteData ? $return->get() : $return;
	}

	public function executeFlow( FlowModel $flow, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$this->logTrace( 'Start Flow', $context, $flow, $data );

		if ( $this->isCurrentScope( $flow, $context ) ) {
			// Check scope first to set queue.
			$data = parent::executeFlow( $flow, $context, $data );
			$this->logTrace( 'Exit Scope from Flow', $context, $flow, $data );
			$this->throwExitScope( $data, $context );
		}

		$data = parent::executeFlow( $flow, $context, $data );

		$this->logTrace( 'Executed Flow', $context, $flow, $data );

		return $data;
	}

	public function executeStep( StepModel $step, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$this->logTrace( 'Start Step', $context, $step, $data );

		if ( $this->isCurrentScope( $step, $context ) ) {
			// Check scope first to set queue.
			$data = parent::executeStep( $step, $context, $data );
			$this->logTrace( 'Exit Scope from Step', $context, $step, $data );
			$this->throwExitScope( $data, $context );
		}

		$data = parent::executeStep( $step, $context, $data );

		$this->logTrace( 'Executed Step', $context, $step, $data );

		return $data;
	}

	public function executeTask( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$task = $config['_class'] ?? '';
		if ( $task ) {
			if ( 'Send' === $task && self::MODE_LIVE !== $context->getPreviewMode() ) {
				$this->logTrace( 'SKIPPED Task ' . $task, $context, $config, $data );
				return $data;
			}

			if ( $this->isCurrentScope( $config, $context ) ) {
				$this->logTrace( 'Exit Scope from Task ' . $task, $context, $config, $data );
				// Check scope first to set queue.
				$this->throwExitScope( $data, $context );
			}

			$this->parsedConfig = $this->parseConfig(
				$config,
				$context,
				$data,
				$this->tasksService->getTask( $task )
			);

			$data = parent::executeTask( $config, $context, $data );

			$this->logTrace( 'Task ' . $task, $context, $config, $data );
		}

		return $data;
	}

	public function logTrace( $message, ExecutionContext $context, $model = null, $data = [] ): void
	{
		if ( ! isset( $this->trace ) ) {
			$this->trace = new ResourceData();
		}

		$key = [];
		foreach ( $context->getAncestors() as $ancestor ) {
			$key[] = $ancestor->getIndex();
		}
		$key[] = $context->getIndex();

		$trace = (array) $this->trace->get( $key, [] );

		if ( $model ) {

			if ( is_array( $model ) ) {
				$name = $model['_label'] ?? '';
				$ref  = $model['_ref'];
			} else {
				$name = $model->getName() ?? '';
				$ref  = $model->getRef();
			}

			$label = $name ? $name . ' (' . $ref . ')' : $ref;
			$message .= ': ' . $label . ' | ' . count( $data );
		}

		$trace[] = $message;

		$this->trace->set( $trace, $key );
	}

	public function throwExitScope( $data, ExecutionContext $context )
	{
		throw new class( $data, $context ) extends \Exception {
			public static bool $SYNCENGINE_EXITPREVIEW = true;
			protected mixed $data;
			protected ExecutionContext $context;

			public function __construct(
				$data,
				ExecutionContext $context,
				string $message = "SyncEngine Exit Preview",
				int $code = 0,
				?\Throwable $previous = null
			) {
				parent::__construct( $message, $code, $previous );
				$this->data    = $data;
				$this->context = $context;
			}

			public function getData(): mixed
			{
				return $this->data;
			}

			public function getContext(): ExecutionContext
			{
				return $this->context;
			}
		};
	}
}
