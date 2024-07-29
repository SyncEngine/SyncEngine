<?php

namespace SyncEngine\Service;

use Symfony\Component\HttpFoundation\Request;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\StepModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Service\Tag\TagParser;

class ExecutePreview extends Execute
{
	const MODE_SAFE = 'safe';
	const MODE_LIVE = 'live';

	protected array $scope;
	protected AutomationModel|bool $fetching = false;
	protected array $testConfig;
	protected iterable $parsedConfig;

	public function schedule( AutomationModel $automation ): void {	/* Nope. */ }

	public function preview( Request $request ): array
	{
		$this->trace()->start();

		$context = new ExecutionContext( $this );
		$context->setTrace( $this->trace() );

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
				$this->trace()->enterTrace( 'Scope' );
				$data = $this->executeScope( json_decode( $scope, true ), $context, $data ?? [] );
				$this->trace()->leaveTrace( 'Scope' );
			} catch ( \Throwable $e ) {
				$context->addError( $e );
			}
		}

		$data   = $data instanceof ExecuteData ? $data : new ExecuteData( $data ?? [] );
		$result = null;

		$scope_data = $data->normalize();

		if ( ! $context->getErrors() ) {
			$this->trace()->resetTraversal();
			$this->trace()->enterTrace( 'Preview' );

			if ( $this->fetching instanceof AutomationModel ) {
				// Parse iteration data.
				$parser = new TagParser( [ 'iterator' => $this->fetching->getIterator() ], false, true );
				$config = $parser->parseTagArray( $config );
			}

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
			$return['Return'] = $result ? $result->normalize() : [];
			$return['Info'] = [
				'count' => count( is_countable( $result ) ? $result : [] ),
			];
		}

		$return['Trace'] = [ $this->trace()->getCurrentTrace() ];

		if ( ! empty( $this->parsedConfig ) ) {
			$return['Config'] = [
				'Raw'    => $this->testConfig,
				'Parsed' => $this->parsedConfig,
			];
		} else {
			$return['Config'] = $this->testConfig;
		}

		$params = $request->request->all();
		foreach ( $params as &$param ) {
			try {
				$param = json_decode( $param, true ) ?: $param;
			} catch ( \Throwable $e ) {
				// Nope.
			}
		}
		$return['Params'] = $params;

		$return['Cache']     = $context->getCache();
		$return['Variables'] = $context->getVariables();

		$return = [
			'success' => empty( $errors ),
			'data'    => $return,
		];

		if ( $scope ) {
			$return['source'] = $scope_data;
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
					$entity['_instance'] = AutomationModel::get( $entity['id'] ) ?? AutomationModel::create();
				break;
				case 'Flow':
					$entity['_instance'] = FlowModel::get( $entity['id'] ) ?? FlowModel::create();
				break;
				case 'Step':
					$entity['_instance'] = StepModel::get( $entity['id'] ) ?? StepModel::create();
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
					$data = new ExecuteData( $this->execute( $startEntity, $context, $data ) );
				break;
				case $startEntity instanceof FlowModel:
					$data = $this->executeFlow( $startEntity, $context, new ExecuteData( $data ?? [] ) );
				break;
				case $startEntity instanceof StepModel:
					$data = $this->executeStep( $startEntity, $context, new ExecuteData( $data ?? [] ) );
				break;
				default:
					// Do not translate for storage.
					$this->trace()->addLog( 'Invalid Scope' );
					$data = new ExecuteData( $data ?? [] );
				break;
			}
		} catch ( \Throwable $e ) {
			if ( ! isset( $e::$SYNCENGINE_EXITPREVIEW ) ) {
				// Do not translate for storage.
				$this->trace()->addLog( 'Scope errored' );
				throw $e;
			}

			$data = $e->getData();
		}

		// Do not translate for storage.
		$this->trace()->addLog( 'Scope executed' );
		$this->scope = [];

		return $data;
	}

	public function execute( AutomationModel $automation, ExecutionContext $context, $data = null ): array
	{
		$this->trace()->enterTrace( $automation );

		$automation->endIterator();

		// Start new iteration. Will set to 1 if it's a new loop.
		$automation->nextIteration();

		try {
			$this->fetching = $automation;
			$data           = $this->fetch( $automation, $context, $data );
			$this->fetching = false;
		} catch ( \Throwable $e ) {
			if ( isset( $e::$SYNCENGINE_EXITPREVIEW ) ) {
				$this->trace()->leaveTrace( $automation );
				throw $e; // Exit scope.
			}

			$automation->endIterator();
			$context->addError( $e );
		}

		$return = [];

		if ( $data instanceof ExecuteData && $data->has() ) {

			$this->trace()->enterTrace( 'Actions' );

			$actions = $automation->getActions();
			if ( $actions ) {
				try {
					$return = $this->executeTasks( $actions, $context, $data );
				} catch ( \Throwable $e ) {
					if ( isset( $e::$SYNCENGINE_EXITPREVIEW ) ) {
						$this->trace()->leaveTrace( 'Actions' );
						$this->trace()->leaveTrace( $automation );
						throw $e; // Exit scope.
					}

					$this->trace()->addError( $e->getMessage() );
					$automation->endIterator();
					$context->addError( $e );
				}
			}

			$this->trace()->leaveTrace( 'Actions' );

		} else {
			$context->addError( 'No data found' );
		}

		$automation->endIterator();

		$this->trace()->leaveTrace( $automation );

		return $return instanceof ExecuteData ? $return->get() : $return;
	}

	public function executeFlow( FlowModel $flow, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		if ( $this->isCurrentScope( $flow, $context ) ) {
			// Check scope first to set queue.
			$data = parent::executeFlow( $flow, $context, $data );
			$this->throwExitScope( $data, $context );
		}

		return parent::executeFlow( $flow, $context, $data );
	}

	public function executeStep( StepModel $step, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		if ( $this->isCurrentScope( $step, $context ) ) {
			// Check scope first to set queue.
			$data = parent::executeStep( $step, $context, $data );
			$this->throwExitScope( $data, $context );
		}

		return parent::executeStep( $step, $context, $data );
	}

	public function executeTask( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$task = $config['_class'] ?? '';

		if ( ! empty( $config['_disabled'] ) ) {
			// Do not translate for storage.
			$context->addLog( 'Disabled Task' );
			return $data;
		}

		if ( $task ) {
			if ( 'Send' === $task && self::MODE_LIVE !== $context->getPreviewMode() ) {
				// Do not translate for storage.
				$context->addLog( 'Skipped Task by preview mode' );
				return $data;
			}

			if ( $this->isCurrentScope( $config, $context ) ) {
				// Check scope first to set queue.
				$this->throwExitScope( $data, $context );
			}

			$this->setParsedConfig(
				$config,
				$context,
				$data,
				TaskModel::get( $task )
			);

			$data = parent::executeTask( $config, $context, $data );
		}

		return $data;
	}

	public function setParsedConfig( $config, $context, $data, $model )
	{
		if ( ! empty( $this->scope ) ) {
			// Not while running scope.
			return;
		}

		$parsedConfig = $this->parseConfig( $config, $context, $data, $model );

		if ( empty( $this->parsedConfig ) ) {
			$this->parsedConfig = $parsedConfig;
			return;
		}

		$this->parsedConfig = $this->setConfigByRef( $this->parsedConfig, $parsedConfig, $config['_ref'] );
	}

	private function setConfigByRef( $config, $refConfig, $ref ): iterable
	{
		if ( isset( $config['_ref'] ) && $config['_ref'] === $ref ) {
			return $refConfig;
		}

		foreach ( $config as $key => $val ) {
			if ( is_iterable( $val ) ) {
				$config[ $key ] = $this->setConfigByRef( $val, $refConfig, $ref );
			}
		}

		return $config;
	}

	public function throwExitScope( ExecuteData $data, ExecutionContext $context )
	{
		// Do not translate for storage.
		$this->trace()->addLog( 'Exit Scope' );
		throw new class( $data, $context ) extends \Exception {
			public static bool $SYNCENGINE_EXITPREVIEW = true;
			protected ExecuteData $data;
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

			public function getData(): ExecuteData
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
