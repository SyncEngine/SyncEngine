<?php

namespace SyncEngine\Service;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Contracts\Translation\TranslatorInterface;
use SyncEngine\EventDispatcher\Event\ExecuteEvent;
use SyncEngine\Exception\ExecuteException;
use SyncEngine\Exception\ExecuteStopException;
use SyncEngine\Exception\NoResultsException;
use SyncEngine\Messenger\Message\AutomationBatch;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\Enum\AutomationEventType;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\Tag\TagParser;
use SyncEngine\Structure\Data\ConfigData;
use SyncEngine\Structure\Data\ResourceData;

class Execute
{
	const MODE_DEBUG = 'debug';

	protected string $mode = '';

	public function __construct(
		protected readonly MessageBusInterface      $messageBus,
		protected readonly EventDispatcherInterface $eventDispatcher,
		protected readonly TranslatorInterface      $translator,
		protected readonly LoggerInterface          $syncengineLogger,
		protected readonly Notifier                 $notifier,
		protected readonly Vault                    $vault,
	) {}

	public function isDebug(): bool
	{
		return $this->mode === self::MODE_DEBUG;
	}

	public function getMode(): string
	{
		return $this->mode;
	}

	public function setMode( string $mode ): void
	{
		$this->mode = $mode;
	}

	public function logger(): LoggerInterface
	{
		return $this->syncengineLogger;
	}

	public function vault(): Vault
	{
		return $this->vault;
	}

	public function schedule( AutomationModel $automation, ExecuteContext $context, array $stamps = [] ): void
	{
		if ( empty( $stamps ) ) {
			$delay = $automation->getInterval();
			if ( $delay ) {
				$stamps[] = new DelayStamp( $delay * 1000 );
			}
		}

		$traceId = $context->getTrace()?->getId() ?? 0;
		$params  = $context->getRequestParams();
		$query   = $context->getRequestQuery();

		$this->messageBus->dispatch( new AutomationBatch( $automation->getId(), $traceId, $params, $query ), $stamps );
	}

	public function fetch( AutomationModel $automation, ExecuteContext $context, $data = null ): ExecuteData
	{
		$request = null;
		if ( $data instanceof Request ) {
			$request = $data->getContent();
			$data    = [];
		} elseif ( $data instanceof ExecuteData ) {
			$request = $data->get();
			$data    = null;
		} elseif ( ! empty( $data ) ) {
			// Will trigger error because of return type.
			return $data;
		}

		$localBatches = $automation->hasIterator() && 'local' === $automation->getConfig( 'batch_method' );

		if ( ! $data && $localBatches && 1 < $automation->getIteration() ) {
			$data = ExecuteLocalBatch::load( $context->getTrace() )->getBatch( $automation->getIteration() );
			if ( $data ) {
				return $data;
			}
		}

		$sources = (array) $automation->getConfig( 'source' );

		if ( ! $sources ) {
			$context->addLog( 'No sources selected' );
			return ExecuteData::create( $data ?? [] );
		}

		$context->getTrace()?->enterTrace( 'Source' );

		if ( $request && in_array( 'request', $sources ) ) {
			$data = $request;

			$requestConfig = $automation->getConfig( 'request' );
			// @todo Parse data?

			if ( ! empty( $requestConfig['format'] ) ) {
				$data = ( new DataFormatter() )->decodeFormat( $requestConfig['format'], $data );
			}
			if ( ! empty( $requestConfig['param'] ) ) {
				$data = ( new TagParser( $data ) )->parseTag( $requestConfig['param'] );
			}
		}

		// Enforce data type.
		$data = ExecuteData::create( $data ?? [] );

		if ( empty( $data->get() ) && in_array( 'retrieve', $sources ) ) {
			$tasks = $automation->getConfig( 'retrieve' );

			if ( $tasks ) {
				$parser = new TagParser( $this->getFetchTagsResource( $automation, $context ), false, true );
				$tasks  = $parser->parseArray( $tasks );

				foreach ( $tasks as $task ) {
					$data = $this->executeTask( $task, $context, $data );

					if ( $context->getErrors() ) {
						break;
					}
				}
			}
		}

		$context->getTrace()?->leaveTrace( 'Source' );

		if ( $context->getErrors() ) {
			throw new ExecuteException( 'Got errors while fetching source data', $context->getErrors() );
		}

		if ( $localBatches ) {
			$data = ExecuteLocalBatch::create(
				$context->getTrace(),
				$data,
				$automation->getLimit()
			)->getBatch( $automation->getIteration() );
		}

		if ( ! $data instanceof ExecuteData || $data->isEmpty() ) {
			throw new NoResultsException( 'No source data available', $data );
		}

		return $data;
	}

	protected function getFetchTagsResource( AutomationModel $automation, ExecuteContext $context ): array
	{
		return array_replace_recursive( $context->getTagsResource(), $automation->getTagsResource( [], $context ) );
	}

	public function execute( AutomationModel $automation, ExecuteContext $context, $data = null ): array
	{
		$isScheduled = (bool) $automation->getIteration();
		$isMain      = (bool) ! $context->getParent();

		try {
			// Make sure to store the trigger timestamp and running state before continuing.
			if ( ! $isScheduled ) {
				$automation->setEventTimestamp( 'trigger' );
			}
			$automation->setRunning( true );
			$automation->persist( true );

			// Start new iteration. Will set to 1 if it's a new loop.
			$automation->nextIteration();

			if ( ! $context->getTrace() ) {
				$context->registerTrace( TraceModel::create() );
			}

			if ( $isMain ) {
				$context->getTrace()?->start( $context );
			}

			$context->getTrace()?->enterTrace( $automation );

			if ( $isScheduled ) {
				$this->logger()->info( 'Continue automation', [ $automation->getId(), $automation->getName(), $automation->getRef(), $automation->getIteration() ] );
			} else {
				$this->logger()->info( 'Started automation', [ $automation->getId(), $automation->getName(), $automation->getRef() ] );
				$this->executeEvent( $context, 'trigger' );
			}

			try {
				$data = $this->fetch( $automation, $context, $data );
			} catch ( NoResultsException $e ) {
				$errorOnEmpty = $automation->getConfig( 'events.error_on_empty', false );
				if ( $errorOnEmpty ) {
					// This will also set the trace status to as errored.
					$context->addError( $e );
				} else {
					if ( $isMain ) {
						$context->getTrace()?->setStatus( TraceStatus::CANCELLED );
					}
					$context->addLog( $e );
				}
			} catch ( \Throwable $e ) {
				$data = [];
				$context->addError( $e );
			}

			$result   = $data;
			$schedule = false;

			if ( $data instanceof ExecuteData ) {

				if ( ! $isScheduled ) {
					$this->executeEvent( $context, 'start' );
				}

				$context->getTrace()?->enterTrace( 'Actions' );

				$actions = $automation->getActions();
				if ( $actions ) {
					try {
						$result = $this->executeTasks( $actions, $context, $data );
					} catch ( \Throwable $e ) {
						$data = [];
						$context->addError( $e );
					}
				}

				$context->getTrace()?->leaveTrace( 'Actions' );

				if ( ! $automation->hasIterator() || $automation->getLimit() !== count( $data ) ) {
					// Last iteration.
					$automation->endIterator();

					if ( $isMain && ! $context->getErrors() ) {
						$context->getTrace()?->setStatus( TraceStatus::SUCCESS );
					}
				} else {
					$context->getTrace()?->setStatus( TraceStatus::SCHEDULED );

					// Continue iteration.
					$schedule = true;
				}
			} else {
				// End iteration.
				$automation->endIterator();
			}

			$finished = ( ! $schedule && $isMain );

			if ( $finished ) {
				$status = $context->getTrace()?->finish()->getStatus();

				if ( ! $status ) {
					$status = $context->getErrors() ? TraceStatus::SUCCESS : TraceStatus::FAILED;
				}

				$this->executeEvent( $context, $status );
				if ( TraceStatus::STOPPED !== $status ) {
					$this->executeEvent( $context, 'stop' );
				}
			}

			$context->getTrace()?->leaveTrace( $automation );

			if ( $isMain ) {
				$context->getTrace()?->end()->store();
			}

			// Allow automation to be triggered manually or by schedule.
			$automation->setRunning( false );
			// Persist any changes.
			$automation->persist( true );

			if ( $schedule ) {
				$this->schedule( $automation, $context );

				// @todo Log instead of return?
				$message = $this->translator->trans( 'Added to queue!' );
			} else {
				$message = $this->translator->trans( 'Finished executing endpoint.' );
			}

			$errors = $context->getErrors();
			if ( $errors ) {
				// @todo Notify?
				//$this->notifier->sendEmail($e->getMessage());
				return [
					'success' => false,
					'message' => $this->translator->trans( 'There were errors while executing this endpoint.' ),
					'errors'  => $errors,
				];
			}

			if ( $result instanceof ExecuteData ) {
				$result = $result->normalize();
			}

			return [
				'success' => true,
				'message' => $message,
				'data'    => $result ?? [],
			];

		} catch ( ExecuteStopException $e ) {
			return [
				'success' => false,
				'message' => $e->getMessage(),
				'errors'  => $context->getErrors(),
			];
		}
	}

	public function executeEvent( ExecuteContext $context, string|TraceStatus $event ): void
	{
		$eventName = match ( $event ) {
			TraceStatus::FAILED  => 'error',
			TraceStatus::SUCCESS => 'success',
			TraceStatus::STOPPED => 'stop',
			TraceStatus::CANCELLED => 'cancel',
			default => $event instanceof TraceStatus ? $event->value : $event,
		};

		$event = new ExecuteEvent( $this, $context, $eventName );
		$this->eventDispatcher->dispatch( $event, 'syncengine.execute.' . $eventName );
		$this->eventDispatcher->dispatch( $event );

		$automationEvent = AutomationEventType::create( $eventName );
		if ( ! $automationEvent ) {
			return;
		}

		$automation = $context->getAutomation();
		if ( $automation ) {
			$automation->setEventTimestamp( $eventName );

			$actions = $automation->getEventActions( $eventName );

			if ( $actions ) {
				try {
					$context->getTrace()?->enterTrace( 'Event actions: ' . $eventName );
					$this->executeTasks( $actions, $context, new ExecuteData( [] ) );
					$context->getTrace()?->leaveTrace( 'Event actions: ' . $eventName );
				} catch ( \Throwable $e ) {
					$context->addError( $e );
				}
			}
		}
	}

	public function executeFlow( FlowModel $flow, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		$context->getTrace()?->enterTrace( $flow );
		$context->startFlow( $flow );

		$context->setCurrent( ResourceData::create(), 'step' );

		foreach ( $flow->getSequence() as $step ) {
			$context->getTrace()?->enterTrace( $step );

			$stepContext = $context;

			$input = $step->getInputConfig();
			if ( $input->hasValues() ) {
				$data = ExecuteData::create( $this->parseConfig( $input->normalize(), $context, $data, null, [ 'step' => $context->getCurrent( 'step' ) ] ) );
			}

			$variables = $step->getVariablesConfig();
			if ( $variables->hasValues() ) {
				$variables   = $this->parseConfig( $variables->filter()->normalize(), $context, $data, null, [ 'step' => $context->getCurrent( 'step' ) ] );
				$stepContext = new ExecuteContext( $this, parent: $context, variables: $variables );
			}

			$data = $this->executeRoutine( $step->getRoutine(), $stepContext, $data );

			// Add new data to context.
			$steps = $context->getCurrent( 'step' );
			$steps[ $step->getRef() ] = $data;
			$context->setCurrent( $steps, 'step' );

			$context->getTrace()?->leaveTrace( $step );
		}

		$context->setCurrent( null, 'step' );
		$context->endFlow();
		$context->getTrace()?->leaveTrace( $flow );

		return $data;
	}

	public function executeRoutine( RoutineModel $routine, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		$context->getTrace()?->enterTrace( $routine );
		$context->startRoutine( $routine );

		$config = $routine->getConfig();

		$tasks = $config['tasks'] ?? [];
		if ( $tasks ) {
			$conditions = $config['conditions'] ?? [];

			if ( ! empty( $conditions ) ) {

				$conditionResource = array_merge(
					$context->getTagsResource(),
					$routine->getTagsResource( $config ),
					[ 'data' => $data ],
				);

				$parser = new TagParser( $conditionResource );

				$conditions = $parser->parseArray( $conditions );
			}

			if ( empty( $conditions ) || ( new ConditionsValidator() )->validate( $conditions, $data ) ) {
				$data = $this->executeTasks( $tasks, $context, $data );
			} else {
				// Do not translate for storage.
				// @todo Opt-in config for log.
				if ( $this->isDebug() ) {
					$context->addLog( 'Conditions validation failed', $conditions );
				}
			}
		}

		$context->endRoutine();
		$context->getTrace()?->leaveTrace( $routine );

		return $data;
	}

	public function executeTasks( array $tasksList, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		foreach ( $tasksList as $taskConfig ) {
			$data = $this->executeTask( $taskConfig, $context, $data );
		}

		return $data;
	}

	public function executeTask( TaskModel|array $config, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		if ( ! $config instanceof TaskModel ) {
			$task = $config['_class'] ?? '';
		} else {
			$task   = $config;
			$config = $task->getConfig();
		}

		if ( ! empty( $config['_disabled'] ) ) {
			// Leave a trace node.
			$context->getTrace()?->enterTrace( $config, 'Task' )->leaveTrace( $config );

			return $data;
		}

		if ( $task ) {
			$task = TaskModel::get( $task );
			$task->setConfig( $config );
		}

		if ( $task instanceof TaskModel ) {
			$config = $this->parseConfig( $config, $context, $data, $task );

			$context->getTrace()?->enterTrace( $config, 'Task' );
			$context->startTask( $task );

			$data = $task->execute( ConfigData::create( $config ), $context, $data );

			$context->endTask();
			$context->getTrace()?->leaveTrace( $config );
		} else {
			// Do not translate for storage.
			$context->addError( 'Task not found', $config );
		}


		return $data;
	}

	public function parseConfig( array $config, ?ExecuteContext $context = null, $data = null, $model = null, array|ResourceData $resource = [] ): array
	{
		if ( $context ) {
			$resource = array_merge( $context->getTagsResource(), $resource );
		}
		if ( $data ) {
			$resource['data'] = $data;
		}

		$clean = true;
		if ( $model instanceof Taggable ) {
			$clean    = $model->getTags();
			$resource = array_merge( $model->getTagsResource( $config ), $resource );
		}

		// @todo Create an exclusion handler that can be registered for the TagParser?
		$parsed = ( new TagParser( $resource ) )->setCleanMode( $clean )->parseArray( $this->removeNestedModelConfig( $config ) );

		return array_replace_recursive( $config, $parsed );
	}

	public function removeNestedModelConfig( array $config ): array
	{
		foreach ( $config as $key => $value ) {
			if ( ! is_array( $value ) ) {
				continue;
			}

			// Nested entity or service models.
			if ( array_key_exists( '_ref', $value ) && array_key_exists( '_class', $value ) ) {
				unset( $config[ $key ] );
				continue;
			}

			$config[ $key ] = $this->removeNestedModelConfig( $value );
		}

		return $config;
	}
}
