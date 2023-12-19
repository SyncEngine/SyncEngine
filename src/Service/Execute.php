<?php

namespace SyncEngine\Service;

use SyncEngine\Message\AutomationLooper;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\StepModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Model\Interface\Taggable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Psr\Log\LoggerInterface;

class Execute
{
	public function __construct(
		protected readonly MessengerManager $messengerManager,
		protected readonly MessageBusInterface $messageBus,
		protected readonly TranslatorInterface $translator,
		protected readonly LoggerInterface $syncengineLogger,
		protected readonly ExecuteTrace $trace,
	) {}

	public function logger(): LoggerInterface
	{
		return $this->syncengineLogger;
	}

	public function schedule( AutomationModel $automation ): void
	{
		$this->messageBus->dispatch( new AutomationLooper( $automation->getId() ) );
	}

	public function fetch( AutomationModel $automation, ExecutionContext $context, $data = null ): ExecuteData
	{
		$this->trace->enterTrace( 'Source' );

		$request = null;
		if ( $data instanceof Request ) {
			$request = $data->getContent();
			$data    = [];
		} elseif ( $data instanceof ExecuteData ) {
			$request = $data->get();
			$data    = null;
		} elseif ( ! empty( $data ) ) {
			$this->trace->leaveTrace( 'Source' );
			return $data;
		}

		$sources = (array) $automation->getConfig( 'source' );

		if ( $request && in_array( 'request', $sources ) ) {
			$data = $request;

			$requestConfig = $automation->getConfig( 'request' );
			if ( ! empty( $requestConfig['format'] ) ) {
				$data = ( new Formatter() )->decodeFormat( $requestConfig['format'], $data );
			}
			if ( ! empty( $requestConfig['param'] ) ) {
				$data = ( new TagParser( $data ) )->parseTag( $requestConfig['param'] );
			}

			if ( $data ) {
				$automation->setData( $data, 'request' );
			}
		}

		if ( ! $data instanceof ExecuteData ) {
			$data = new ExecuteData( $data ?? [] );
		}

		if ( empty( $data->get() ) && in_array( 'retrieve', $sources ) ) {
			$tasks = $automation->getConfig( 'retrieve' );

			if ( $tasks ) {
				// Parse iteration data.
				if ( $automation->hasIterator() ) {
					$parser = new TagParser( [ 'iterator' => $automation->getIterator() ], false );
					$tasks  = $parser->parseTagArray( $tasks );
				}

				$data = $this->executeTasks( $tasks, $context, $data );
			}
		}

		if ( 'local' === $automation->getConfig( 'batch_method' ) ) {
			$data = $data->slice( $automation->getOffset(), $automation->getLimit() );
		}

		$this->trace->leaveTrace( 'Source' );

		return $data;
	}

	public function execute( AutomationModel $automation, ExecutionContext $context, $data = null ): array
	{
		echo '<pre>';
		var_dump( $automation->getConfig() );
		echo '</pre>';
		$automation->setRunning( true );
		$automation->persist( true );

		// Start new iteration. Will set to 1 if it's a new loop.
		$automation->nextIteration();

		if ( 1 === $automation->getIteration() ) {
			$this->trace->enterTrace( $automation );
			$this->logger()->info( 'Started automation', [ $automation->getId(), $automation->getName(), $automation->getRef() ] );
		} else {
			$this->trace->enterTrace( $automation ); // @todo, continue trace.
			$this->logger()->info( 'Continue automation', [ $automation->getId(), $automation->getName(), $automation->getRef(), $automation->getIteration() ] );
		}

		try {
			$data = $this->fetch( $automation, $context, $data );
		} catch ( \Throwable $e ) {
			$data = [];
			$context->addError( $e );
		}

		$result = [];

		if ( $data instanceof ExecuteData ) {

			$this->trace->enterTrace( 'Actions' );

			$actions = $automation->getActions();
			if ( $actions ) {
				try {
					$result = $this->executeTasks( $actions, $context, $data );
				} catch ( \Throwable $e ) {
					$data = [];
					$this->trace->addError( $e->getMessage() );
					$context->addError( $e );
				}
			}

			if ( ! $automation->hasIterator() || $automation->getLimit() !== count( $data ) ) {
				// Last iteration.
				$automation->endIterator();
			} else {
				// Store before schedule so the iterator is up-to-date.
				$automation->persist( true );

				// Continue iteration.
				$this->schedule( $automation );

				// @todo Log instead of return?
				$result = $this->translator->trans( 'Added to queue!' );
			}

			$this->trace->leaveTrace( 'Actions' );
		} else {
			// End iteration.
			$automation->endIterator();
			$context->addError( $this->translator->trans( 'No data found' ) );
			$this->trace->addLog( 'No data found' );
		}

		$this->messengerManager->handleQueue();

		if ( ! $automation->getIteration() ) {
			$automation->setRunning( false );
		}

		// Persist any changes.
		$automation->persist( true );

		$this->trace->leaveTrace( $automation );

		$errors = $context->getErrors();
		if ( $errors ) {
			return [
				'success' => false,
				'errors'  => $errors,
			];
		}

		if ( $result instanceof ExecuteData ) {
			$result = $result->get();
		}

		return [
			'success' => true,
			'data'    => $result ?? [],
		];
	}

	public function executeFlow( FlowModel $flow, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$this->trace->enterTrace( $flow );
		$context->startFlow( $flow );

		foreach ( $flow->getSteps() as $step ) {
			$data = $this->executeStep( $step, $context, $data );
		}

		$context->endFlow();
		$this->trace->leaveTrace( $flow );

		return $data;
	}

	public function executeStep( StepModel $step, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$this->trace->enterTrace( $step );
		$context->startStep( $step );

		$config = $step->getConfig();

		$tasks = $config['tasks'] ?? [];
		if ( $tasks ) {
			$conditionals = $config['conditionals'] ?? [];

			if ( ! empty( $conditionals ) ) {

				$conditionalResource = array_merge(
					[ 'context' => $context, 'data' => $data ],
					$step->getTagsResource( $config )
				);

				$parser = new TagParser( $conditionalResource );

				$conditionals = $parser->parseTagArray( $conditionals );
			}

			if ( empty( $conditionals ) || $step->validateConditionals( $conditionals, $data ) ) {
				$data = $this->executeTasks( $tasks, $context, $data );
			}
		}

		$context->endStep();
		$this->trace->leaveTrace( $step );

		return $data;
	}

	public function executeTasks( array $tasksList, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		foreach ( $tasksList as $taskConfig ) {
			$data = $this->executeTask( $taskConfig, $context, $data );
		}

		return $data;
	}

	public function executeTask( array $config, ExecutionContext $context, ExecuteData $data ): ExecuteData
	{
		$this->trace->enterTrace( $config );

		if ( ! empty( $config['_disabled'] ) ) {
			$this->trace->addLog( 'Disabled' );

			$this->trace->leaveTrace( $config );
			return $data;
		}

		$task = $config['_class'] ?? '';
		if ( $task ) {
			$task = TaskModel::get( $task );
			if ( $task ) {
				$context->startTask( $task );

				$data = $task->execute(
					$this->parseConfig( $config, $context, $data, $task ),
					$context,
					$data
				);

				$context->endTask();
			}
		} else {
			$this->trace->addLog( 'Task not found' );
		}

		$this->trace->leaveTrace( $config );

		return $data;
	}

	public function parseConfig( array $config, ExecutionContext $context = null, $data = null, $model = null, array|ResourceData $resource = [] ): array
	{
		if ( $context ) {
			$resource['context'] = $context;
		}
		if ( $data ) {
			$resource['data'] = $data;
		}
		if ( $model instanceof Taggable ) {
			$resource = array_merge( $resource, $model->getTagsResource( $config ) );
		}

		// @todo Create an exclusion handler that can be registered for the TagParser?
		$parsed = ( new TagParser( $resource ) )->parseTagArray( $this->removeNestedTasks( $config ) );

		return array_replace_recursive( $config, $parsed );
	}

	public function removeNestedTasks( array $config ): array
	{
		foreach ( $config as $key => $value ) {
			if ( ! is_array( $value ) ) {
				continue;
			}

			// Nested tasks.
			if ( array_key_exists( '_ref', $value ) ) {
				unset( $config[ $key ] );
				continue;
			}

			$config[ $key ] = $this->removeNestedTasks( $value );
		}

		return $config;
	}
}
