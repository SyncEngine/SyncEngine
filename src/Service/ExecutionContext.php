<?php

namespace SyncEngine\Service;

use Doctrine\ORM\EntityManagerInterface;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\StepModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\Helper\EntityManagerSandbox;

class ExecutionContext extends Context
{
	protected int $current = 0;
	protected array $request; // @todo
	protected AutomationModel $automation;
	protected Execute $execute;
	protected ExecutionContext $parent;
	protected ResourceData $cache;
	protected TraceModel $trace;
	protected array $variables = [];
	protected array $logs = [];
	protected array $errors = [];
	protected string $preview = '';

	public function __construct( Execute $execute, AutomationModel $automation = null, $parent = null )
	{
		$this->execute = $execute;

		if ( $parent instanceof self ) {
			$this->parent    = $parent;
			$this->cache     = $parent->getCache(); // Keep object reference.
			$this->variables = $parent->getVariables();
			$this->trace     = $parent->getTrace();
			$this->setPreviewMode( $parent->getPreviewMode() );
		} else {
			$this->cache   = new ResourceData( [] );
		}

		if ( $automation ) {
			$this->automation = $automation;
			$this->variables  = array_replace( $this->variables, $automation->getVariables() );
		}
	}

	public function getExecuteService(): Execute
	{
		return $this->execute;
	}

	public function getEntityManager(): EntityManagerInterface
	{
		if ( $this->isPreview( ExecutePreview::MODE_SAFE ) ) {
			return new EntityManagerSandbox( DefaultController::getEntityManager() );
		}

		return DefaultController::getEntityManager();
	}

	public function setTrace( TraceModel $trace ): void
	{
		$this->trace = $trace;
	}

	public function getTrace(): ?TraceModel
	{
		return $this->trace ?? null;
	}

	public function setPreviewMode( string $mode ): void
	{
		$this->preview = $mode;
	}

	public function getPreviewMode(): string
	{
		return $this->preview;
	}

	public function isPreview( string $type = '' ): bool
	{
		if ( $type ) {
			return $type === $this->getPreviewMode();
		}

		return ! empty( $this->preview );
	}

	public function getTagsResource(): array
	{
		return [
			'context'   => $this,
			'cache'     => $this->getCache(),
			'variables' => $this->getVariables(),
			// Make get handler in tags resource?
			'vault'     => $this->getExecuteService()->vault()->get(),
		];
	}

	public function getVariables(): array
	{
		return $this->variables;
	}

	public function getCacheTag( $ref ): mixed
	{
		return $this->cache[ $ref ] ?? null;
	}

	public function setCacheTag( $ref, $value ): void
	{
		$this->cache[ $ref ] = $value;
	}

	public function getCache(): ResourceData
	{
		return $this->cache;
	}

	public function getRoot()
	{
		return reset( $this->context );
	}

	public function getIndex(): int
	{
		return $this->current;
	}

	public function getRequest(): array|null
	{
		return $this->request;
	}

	/**
	 * @return self[]
	 */
	public function getAncestors(): array
	{
		$ancestors = [];

		$parent = $this->getParent();
		if ( ! $parent ) {
			return $ancestors;
		}

		$ancestors[] = $parent;

		do {
			$ancestors[] = $parent;
		} while ( $parent = $parent->getParent() );

		return array_reverse( $ancestors );
	}

	public function getParent(): ?ExecutionContext
	{
		return $this->parent ?? null;
	}

	public function getAutomation(): ?AutomationModel
	{
		return $this->automation ?? null;
	}

	public function getCurrent( $type = '' ): mixed
	{
		if ( $type ) {
			return $this->context[ $this->getIndex() ][ $type ] ?? null;
		}

		return $this->context[ $this->getIndex() ] ?? null;
	}

	public function getCurrentFlow(): ?FlowModel
	{
		return $this->getCurrent( 'flow' );
	}

	public function getCurrentStep(): ?StepModel
	{
		return $this->getCurrent( 'step' );
	}

	public function getCurrentTask(): ?TaskModel
	{
		return $this->getCurrent( 'task' );
	}

	public function setCurrent( $value, $type = '' )
	{
		$this->context[ $this->getIndex() ][ $type ] = $value;
	}

	public function startFlow( FlowModel $flow )
	{
		$this->setCurrent( $flow, 'flow' );
	}

	public function startStep( StepModel $step )
	{
		$this->setCurrent( $step, 'step' );
	}

	public function startTask( TaskModel $task )
	{
		$this->setCurrent( $task, 'task' );
	}

	public function endFlow()
	{
		$this->setCurrent( null, 'flow' );
	}

	public function endStep()
	{
		$this->setCurrent( null, 'step' );
	}

	public function endTask()
	{
		$this->setCurrent( null, 'task' );
	}

	public function next( $new = true, $auto_create_new = true )
	{
		if ( ! $new ) {
			$next = $this->getCurrent( '_next' );
			if ( $next ) {
				$this->current = $next;

				return $this->getCurrent();
			}
			if ( ! $auto_create_new ) {
				// @todo error.
				return null;
			}
		}

		$previous        = $this->current;
		$this->context[] = [];
		$next            = array_key_last( $this->context );
		$this->setCurrent( $next, '_next' );
		$this->current = $next;
		$this->setCurrent( $previous, '_prev' );

		return $this->getCurrent();
	}

	public function previous()
	{
		$previous      = $this->getCurrent( '_prev' );
		$this->current = $previous;

		return $this->getCurrent();
	}

	public function descend( AutomationModel $automation ): ExecutionContext
	{
		return new ExecutionContext( $this->execute, $automation, $this );
	}

	public function ascend(): ?ExecutionContext
	{
		return $this->getParent() ?? $this;
	}

	public function getLogs(): array
	{
		return $this->logs;
	}

	/**
	 * @param  \Throwable|string  $message
	 * @param  mixed              $info
	 *
	 * @return void
	 */
	public function addLog( \Throwable|array|string $message, mixed $info = null, ExecutionContext $origin_context = null ): void
	{
		// Update parent logs.
		$this->getParent()?->addLog( $message, $info, $origin_context ?? $this );

		$message = $this->parseMessage( $message, $info, $origin_context ?? $this );

		if ( $this->getTrace() ) {
			$this->getTrace()->addLog( $message );
		}

		$this->logs[] = $message;
	}

	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * @param  \Throwable|string  $message
	 * @param  mixed              $info
	 *
	 * @return void
	 */
	public function addError( \Throwable|array|string $message, mixed $info = null, ExecutionContext $origin_context = null ): void
	{
		// Update parent errors.
		$this->getParent()?->addError( $message, $info, $origin_context ?? $this );

		$message = $this->parseMessage( $message, $info, $origin_context ?? $this );

		if ( $this->getTrace() ) {
			$this->getTrace()->addError( $message );
		}

		$this->errors[] = $message;
	}

	/**
	 * @param  \Throwable|string  $message
	 * @param  mixed              $info
	 *
	 * @return array
	 */
	public function parseMessage( \Throwable|array|string $message, mixed $info = null, ExecutionContext $context = null ): array
	{
		$trace = [
			'message'    => $message,
			'automation' => $context->getAutomation()?->getId(),
		];

		if ( $message instanceof \Throwable ) {
			if ( $message instanceof \ErrorException ) {
				throw $message; // PHP Error.
			}

			$trace['message'] = json_decode( $message->getMessage(), true ) ?? $message->getMessage();

			if ( is_callable( [ $message, 'getResponse' ] ) ) {
				$response = $message->getResponse();
				$trace['response'] = [];
				if ( is_callable( [ $response, 'getInfo' ] ) ) {
					$trace['response']['info'] = $response->getInfo();
				}
				if ( is_callable( [ $response, 'getHeaders' ] ) ) {
					$trace['response']['headers'] = $response->getHeaders();
				}
				if ( is_callable( [ $response, 'getContent' ] ) ) {
					$trace['response']['content'] = $response->getContent();
				}
			}

			$trace['line'] = $message->getLine();
			$trace['file'] = $message->getFile();

			// @todo if debug.
			$trace['trace']  = explode( "\n", $message->getTraceAsString() );
			$trace['_class'] = $message::class;
		}

		if ( $info ) {
			$trace['info'] = $info;
		}

		$flow = $context->getCurrentFlow();
		if ( $flow ) {
			$trace['flow'] = $flow->getId();
		}

		$step = $context->getCurrentStep();
		if ( $step ) {
			$trace['step'] = $step->getId();
		}

		$task = $context->getCurrentTask();
		if ( $task ) {
			$trace['task'] = $task->getClassLocator();
		}

		return $trace;
	}

	public function offsetExists( mixed $offset ): bool
	{
		if ( is_numeric( $offset ) ) {
			return parent::offsetExists( $offset );
		}
		switch ( $offset ) {
			case 'cache':
				return true;
			case 'automation':
				return ! empty( $this->automation );
			case 'request':
				return ! empty( $this->request );
		}

		return (bool) $this->getCurrent( $offset );
	}

	public function offsetGet( mixed $offset ): mixed
	{
		if ( is_numeric( $offset ) ) {
			parent::offsetGet( $offset );
		}
		switch ( $offset ) {
			case 'cache':
				return $this->getCache();
			case 'automation':
				return $this->getAutomation();
			case 'request':
				return $this->getRequest();
		}

		return $this->getCurrent( $offset );
	}

	public function offsetSet( mixed $offset, mixed $value ): void
	{
		if ( is_numeric( $offset ) ) {
			parent::offsetSet( $offset, $value );
		}
		$this->setCurrent( $value, $offset );
	}

	public function offsetUnset( mixed $offset ): void
	{
		if ( is_numeric( $offset ) ) {
			parent::offsetUnset( $offset );
		}
		unset( $this->context[ $this->getIndex() ][ $offset ] );
	}
}
