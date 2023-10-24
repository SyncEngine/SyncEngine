<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Entity\Automation;
use App\Entity\Flow;
use App\Entity\Step;
use App\Message\AutomationLooper;
use App\Model\AutomationModel;
use App\Model\FlowModel;
use App\Model\StepModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

class ExecutePreview extends Execute
{
	const MODE_SAFE = 'safe';
	const MODE_LIVE = 'live';

	protected array $scope;
	protected array $testConfig;

	public function schedule( AutomationModel $automation ): void
	{
		// Nope.
	}

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

		if ( ! $context->getErrors() ) {
			try {
				switch ( $request->get( 'type' ) ) {
					case 'task':
						$return = $this->executeTask( $config, $context, $data );
					break;
					case 'step':
						$step = new StepModel( new Step() );
						$step->setConfig( $config );

						$return = $this->executeStep( $step, $context, $data );
					break;
					case 'flow':
						$flow = new FlowModel( new Flow() );
						$flow->setConfig( $config );

						$return = $this->executeFlow( $flow, $context, $data );
					break;
					case 'automation':
						$automation = new AutomationModel( new Automation() );
						$automation->setConfig( $config );

						$return = $this->execute( $automation, $context, $data );
					break;
					default:
						$context->addError( 'No preview type set' );
					break;
				}
			} catch ( \Throwable $e ) {
				$context->addError( $e );
			}
		}

		$errors = $context->getErrors();
		if ( $errors ) {
			$return = $errors;
		}

		return [
			'success' => empty( $errors ),
			'source'  => $data ?? [],
			'data'    => [
				'Return' => $return ?? [],
				'Config' => $this->testConfig,
			],
		];
	}

	public function isCurrentScope( $item, ExecutionContext $context ): bool
	{
		if ( ! $this->scope ) {
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
			$this->scope['current'] ++;

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

	public function executeScope( array $scope, ExecutionContext $context, $data = null ): array
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
					$data = $this->executeFlow( $startEntity, $context, $data );
				break;
				case $startEntity instanceof StepModel:
					$data = $this->executeStep( $startEntity, $context, $data );
				break;
			}
		} catch ( \Throwable $e ) {
			if ( ! isset( $e::$SYNCENGINE_EXITPREVIEW ) ) {
				throw $e;
			}

			$data = $e->getData();
		}

		$this->scope = [];

		return $data;
	}

	public function execute( AutomationModel $automation, ExecutionContext $context, $data = null ): array
	{
		$automation->endIterator();

		if ( $this->isCurrentScope( $automation, $context ) ) {
			$data = $this->fetch( $automation, $context, $data );
			$this->throwExitScope( $data, $context );
		}

		$data = $this->fetch( $automation, $context, $data );
		$return = $data;

		if ( $data ) {
			$flow = FlowModel::get( $automation->getFlow() );
			if ( $flow ) {
				try {
					$return = $this->executeFlow( $flow, $context, $data );
				} catch ( \Throwable $e ) {
					if ( isset( $e::$SYNCENGINE_EXITPREVIEW ) ) {
						throw $e; // Continue.
					}

					$data = [];
					$context->addError( $e );
				}
			}
		} else {
			$context->addError( 'No data found' );
		}

		return $return ?? [];
	}

	public function executeFlow( FlowModel $flow, ExecutionContext $context, $data ): array
	{
		if ( $this->isCurrentScope( $flow, $context ) ) {
			// Check scope first to set queue.
			$data = parent::executeFlow( $flow, $context, $data );
			$this->throwExitScope( $data, $context );
		}

		return parent::executeFlow( $flow, $context, $data );
	}

	public function executeStep( StepModel $step, ExecutionContext $context, $data ): array
	{
		if ( $this->isCurrentScope( $step, $context ) ) {
			// Check scope first to set queue.
			$data = parent::executeStep( $step, $context, $data );
			$this->throwExitScope( $data, $context );
		}

		return parent::executeStep( $step, $context, $data );
	}

	public function executeTask( array $config, ExecutionContext $context, $data ): array
	{
		$task = $config['_class'] ?? '';
		if ( $task ) {
			if ( 'Send' === $task && self::MODE_LIVE !== $context->getPreviewMode() ) {
				return $data;
			}

			if ( $this->isCurrentScope( $config, $context ) ) {
				// Check scope first to set queue.
				$this->throwExitScope( $data, $context );
			}

			$data = parent::executeTask( $config, $context, $data );
		}

		return $data;
	}

	public function throwExitScope( $data, ExecutionContext $context )
	{
		throw new class( $data, $context ) extends \Exception {
			public static bool $SYNCENGINE_EXITPREVIEW = true;
			protected mixed $data;
			protected ExecutionContext $context;

			public function __construct( $data, ExecutionContext $context, string $message = "SyncEngine Exit Preview", int $code = 0, ?\Throwable $previous = null )
			{
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
