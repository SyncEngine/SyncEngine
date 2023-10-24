<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Model\AutomationModel;
use App\Model\FlowModel;
use App\Model\StepModel;
use App\Model\TaskModel;
use Doctrine\ORM\EntityManagerInterface;

class ExecutionContext extends Context
{
	protected int $current = 0;
	protected array $request; // @todo
	protected ExecutionContext $parent;
	protected Execute $execute;
	protected AutomationModel $automation;
	protected array $cache = [];
	protected array $errors = [];
	protected string $preview = '';

	public function __construct( Execute $execute, AutomationModel $automation = null )
	{
		$this->execute    = $execute;

		if ( $automation ) {
			$this->automation = $automation;
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

	public function getContextCache( $ref ): mixed
	{
		return $this->cache[ $ref ] ?? null;
	}

	public function setContextCache( $ref, $value ): void
	{
		$this->cache[ $ref ] = $value;
	}

	public function getCache(): array
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

	public function getAutomation(): AutomationModel|null
	{
		return $this->automation;
	}

	public function getCurrent( $type = '' ): mixed
	{
		if ( $type ) {
			return $this->context[ $this->getIndex() ][ $type ] ?? null;
		}

		return $this->context[ $this->getIndex() ] ?? null;
	}

	public function getCurrentFlow(): FlowModel|null
	{
		return $this->getCurrent( 'flow' );
	}

	public function getCurrentStep(): StepModel|null
	{
		return $this->getCurrent( 'step' );
	}

	public function getCurrentTask(): TaskModel|null
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
		$context = new ExecutionContext( $this->execute, $automation );
		$context->parent = $this;
		return $context;
	}

	public function ascend(): ExecutionContext
	{
		return $this->parent;
	}

	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * @param \Throwable|string $message
	 * @param mixed $info
	 * @return void
	 */
	public function addError( \Throwable|string $message, mixed $info = null ): void
	{
		$error = [
			'message' => $message,
			'automation' => $this->getAutomation()->getId(),
		];

		if ( $message instanceof \Throwable ) {
			if ( $message instanceof \ErrorException ) {
				throw $message; // PHP Error.
			}

			$error['message'] = $message->getMessage();

			// @todo if debug.
			$error['trace'] = $message->getTraceAsString();
			$error['_class'] = $message::class;
		}

		if ( $info ) {
			$error[ 'info' ] = $info;
		}

		$flow = $this->getCurrentFlow();
		if ( $flow ) {
			$error[ 'flow' ] = $flow->getId();
		}

		$step = $this->getCurrentStep();
		if ( $step ) {
			$error[ 'step' ] = $step->getId();
		}

		$task = $this->getCurrentTask();
		if ( $task ) {
			$error[ 'task' ] = $task->getClassName();
		}

		$this->errors[] = $error;
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
