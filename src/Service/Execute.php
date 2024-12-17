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
use SyncEngine\Exception\NoResultsException;
use SyncEngine\Messenger\Message\AutomationBatch;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\Enum\AutomationEventType;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\Interface\Taggable;
use SyncEngine\Model\StepModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\Data\ResourceData;
use SyncEngine\Service\Tag\TagParser;

class Execute
{
	const MODE_DEBUG = 'debug';

	protected string $mode = '';

	protected ?TraceModel $trace;

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

	public function trace(): ?TraceModel
	{
		if ( ! isset( $this->trace ) ) {
			$this->trace = TraceModel::create();
		}
		return $this->trace;
	}

	public function vault()
	{
		return $this->vault;
	}

	public function schedule( AutomationModel $automation ): void
	{
		$stamps = [];

		$delay = $automation->getInterval();
		if ( $delay ) {
			$stamps[] = new DelayStamp( $delay * 1000 );
		}

		$this->messageBus->dispatch( new AutomationBatch( $automation->getId(), $this->trace()?->getId() ?? 0 ), $stamps );
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
			$data = ExecuteLocalBatch::load( $this->trace() )->getBatch( $automation->getIteration() );
			if ( $data ) {
				return $data;
			}
		}

		$sources = (array) $automation->getConfig( 'source' );

		if ( ! $sources ) {
			$context->addLog( 'No sources selected' );
			return ExecuteData::create( $data ?? [] );
		}

		$this->trace()?->enterTrace( 'Source' );

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

			if ( $data ) {
				$automation->setData( $data, 'request' );
			}
		}

		// Enforce data type.
		$data = ExecuteData::create( $data ?? [] );

		if ( empty( $data->get() ) && in_array( 'retrieve', $sources ) ) {
			$tasks = $automation->getConfig( 'retrieve' );

			if ( $tasks ) {
				$parser = new TagParser( $automation->getTagsResource( [], $context ), false, true );
				$tasks  = $parser->parseArray( $tasks );

				foreach ( $tasks as $task ) {
					$data = $this->executeTask( $task, $context, $data );

					if ( $context->getErrors() ) {
						break;
					}
				}
			}
		}

		$this->trace()?->leaveTrace( 'Source' );

		if ( $context->getErrors() ) {
			throw new ExecuteException( 'Got errors while fetching source data', $context->getErrors() );
		}

		if ( $localBatches ) {
			$data = ExecuteLocalBatch::create(
				$this->trace(),
				$data,
				$automation->getLimit()
			)->getBatch( $automation->getIteration() );
		}

		if ( $data->isEmpty() ) {
			throw new NoResultsException( 'No source data available', $data );
		}

		return $data;
	}

	public function execute( AutomationModel $automation, ExecuteContext $context, $data = null ): array
	{
		$isScheduled = (bool) $automation->getIteration();
		$isMain      = (bool) ! $context->getParent();

		// Make sure to store the trigger timestamp and running state before continuing.
		if ( ! $isScheduled ) {
			$automation->setEventTimestamp( 'trigger' );
		}
		$automation->setRunning( true );
		$automation->persist( true );

		// Start new iteration. Will set to 1 if it's a new loop.
		$automation->nextIteration();

		if ( $context->getTrace() ) {
			$this->trace = $context->getTrace();
		}

		if ( $isMain ) {
			$this->trace()?->start( $automation );
		}

		$this->trace()?->enterTrace( $automation );

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
					$this->trace()?->setStatus( TraceStatus::CANCELLED );
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

			$this->trace()?->enterTrace( 'Actions' );

			$actions = $automation->getActions();
			if ( $actions ) {
				try {
					$result = $this->executeTasks( $actions, $context, $data );
				} catch ( \Throwable $e ) {
					$data = [];
					$context->addError( $e );
				}
			}

			$this->trace()?->leaveTrace( 'Actions' );

			if ( ! $automation->hasIterator() || $automation->getLimit() !== count( $data ) ) {
				// Last iteration.
				$automation->endIterator();

				if ( $isMain && ! $context->getErrors() ) {
					$this->trace()?->setStatus( TraceStatus::SUCCESS );
				}
			} else {
				$this->trace()?->setStatus( TraceStatus::RUNNING );

				// Continue iteration.
				$schedule = true;
			}
		} else {
			// End iteration.
			$automation->endIterator();
		}

		// Automation finished.
		if ( ! $automation->getIteration() ) {
			$automation->setRunning( false );

			$status = $this->trace()?->getStatus();

			if ( ! $status ) {
				$status = $context->getErrors() ? TraceStatus::SUCCESS : TraceStatus::FAILED;
			}

			$this->executeEvent( $context, $status );
			if ( TraceStatus::STOPPED !== $status ) {
				$this->executeEvent( $context, 'stop' );
			}
		}

		$this->trace()?->leaveTrace( $automation );

		if ( $isMain ) {
			$this->trace()?->store( $automation );
		}

		// Persist any changes.
		$automation->persist( true );

		if ( $schedule ) {
			$this->schedule( $automation );

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

		$automationEvent = AutomationEventType::create( $eventName );
		if ( ! $automationEvent ) {
			return;
		}

		$automation = $context->getAutomation();
		if ( $automation ) {
			$automation->setEventTimestamp( $eventName );

			$actions = $automation->getEventActions( $eventName );

			if ( $actions ) {
				$this->trace()?->enterTrace( 'Event actions: ' . $eventName );
				$this->executeTasks( $actions, $context, new ExecuteData( [] ) );
				$this->trace()?->leaveTrace( 'Event actions: ' . $eventName );
			}
		}
	}

	public function executeFlow( FlowModel $flow, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		$this->trace()?->enterTrace( $flow );
		$context->startFlow( $flow );

		foreach ( $flow->getSteps() as $step ) {
			$data = $this->executeStep( $step, $context, $data );
		}

		$context->endFlow();
		$this->trace()?->leaveTrace( $flow );

		return $data;
	}

	public function executeStep( StepModel $step, ExecuteContext $context, ExecuteData $data ): ExecuteData
	{
		$this->trace()?->enterTrace( $step );
		$context->startStep( $step );

		$config = $step->getConfig();

		$tasks = $config['tasks'] ?? [];
		if ( $tasks ) {
			$conditions = $config['conditions'] ?? [];

			if ( ! empty( $conditions ) ) {

				$conditionResource = array_merge(
					$context->getTagsResource(),
					$step->getTagsResource( $config ),
					[ 'data' => $data ],
				);

				$parser = new TagParser( $conditionResource );

				$conditions = $parser->parseArray( $conditions );
			}

			if ( empty( $conditions ) || $step->validateConditions( $conditions, $data ) ) {
				$data = $this->executeTasks( $tasks, $context, $data );
			} else {
				// Do not translate for storage.
				// @todo Opt-in config for log.
				if ( $this->isDebug() ) {
					$context->addLog( 'Conditions validation failed', $conditions );
				}
			}
		}

		$context->endStep();
		$this->trace()?->leaveTrace( $step );

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
			$this->trace()?->enterTrace( $config, 'Task' )->leaveTrace( $config );

			return $data;
		}

		if ( $task ) {
			$task = TaskModel::get( $task );
			$task->setConfig( $config );
		}

		$this->trace()?->enterTrace( $config, 'Task' );

		if ( $task instanceof TaskModel ) {
			$context->startTask( $task );

			$data = $task->execute(
				$this->parseConfig( $config, $context, $data, $task ),
				$context,
				$data
			);

			$context->endTask();
		} else {
			// Do not translate for storage.
			$context->addLog( 'Task not found' );
		}

		$this->trace()?->leaveTrace( $config );

		return $data;
	}

	public function parseConfig( array $config, ExecuteContext $context = null, $data = null, $model = null, array|ResourceData $resource = [] ): array
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
