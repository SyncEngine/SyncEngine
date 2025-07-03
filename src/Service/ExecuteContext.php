<?php

namespace SyncEngine\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\HttpFoundation\Request;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\FlowModel;
use SyncEngine\Model\RoutineModel;
use SyncEngine\Model\TaskModel;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\Tag\TagParser;
use SyncEngine\Service\Trace\Enum\TraceLogType;
use SyncEngine\Service\Trace\TraceContext;
use SyncEngine\Service\Trace\TraceLog;
use SyncEngine\Structure\Context;
use SyncEngine\Structure\Data\ResourceData;

#[Exclude]
class ExecuteContext extends Context
{
	protected readonly Execute $execute;
	protected readonly ExecuteContext $parent;
	protected readonly ResourceData $cache;
	protected readonly ?AutomationModel $automation;
	/** @readonly */
	protected ?TraceModel $trace;
	/** @readonly */
	protected ?Request $request;
	/** @readonly */
	protected array $variables = [];
	protected array $logs = [];
	protected array $errors = [];
	protected int $current = 0;

	public function __construct(
		Execute $execute,
		?AutomationModel $automation = null,
		?ExecuteContext $parent = null,
		array $variables = []
	) {
		$this->execute = $execute;

		if ( $parent instanceof self ) {
			$this->parent     = $parent;
			$this->context[]  = $parent->getCurrent();
			$this->cache      = $parent->getCache(); // Keep object reference.
			$this->trace      = $parent->getTrace();
			$this->request    = $parent->getRequest();
			$this->variables  = array_replace( $this->variables, $parent->getVariables() );
			if ( ! $automation ) {
				$automation = $parent->getAutomation();
			}
		} else {
			$this->cache   = new ResourceData( [] );
		}

		if ( $automation ) {
			$this->automation = $automation;
			if ( ! $parent || $this->automation !== $parent->getAutomation() ) {
				// @todo Keep parsed variables or override with sub-automation new variables?
				// Note: In case the sub-automation relies on request params, this might get unindented results.
				$this->variables = array_replace( $this->variables, $automation->getVariables() );
			}
		}

		if ( $variables ) {
			$this->variables = array_replace( $this->variables, $variables );
		}
	}

	public function getExecuteService(): Execute
	{
		return $this->execute;
	}

	public function getEntityManager(): EntityManagerInterface
	{
		return DefaultController::getEntityManager();
	}

	public function registerTrace( TraceModel $trace ): void
	{
		if ( isset( $this->trace ) ) {
			throw new \LogicException( '{param} already set. Please create a new context.', [ 'param' => 'Trace' ] );
		}

		$this->trace = $trace;
	}

	public function getTrace(): ?TraceModel
	{
		return $this->trace ?? null;
	}

	public function registerRequest( Request $request ): void
	{
		if ( isset( $this->request ) ) {
			throw new \LogicException( '{param} already set. Please create a new context.', [ 'param' => 'Request' ] );
		}

		$this->request = $request;
	}

	public function getRequest(): ?Request
	{
		return $this->request ?? null;
	}

	public function getRequestParams(): array
	{
		return isset( $this->request ) ? $this->request->request->all() : [];
	}

	public function getRequestQuery(): array
	{
		return isset( $this->request ) ? $this->request->query->all() : [];
	}

	public function isPreview( string $type = '' ): bool
	{
		return false;
	}

	public function parseTag( string|array $tag, array $resource = [] ): mixed
	{
		$resource = array_merge( $this->getTagsResource(), $resource );
		$parser   = new TagParser( $resource );

		return match ( gettype( $tag ) ) {
			'string' => $parser->parseString( $tag ),
			default => $parser->parseArray( $tag ),
		};
	}

	public function getTagsResource(): array
	{
		return [
			'context'   => $this,
			'cache'     => $this->getCache(),
			'variables' => $this->getVariables(),
			'request'   => $this->getRequestParams(),
			// Make get handler in tags resource?
			'vault'     => $this->getExecuteService()->vault(),
			'errors'    => $this->getErrors(),
		];
	}

	public function getVariables(): array
	{
		return $this->variables;
	}

	public function getCache(): ResourceData
	{
		return $this->cache;
	}

	public function getCacheTag( $ref ): mixed
	{
		return $this->cache->get( $ref );
	}

	public function setCacheTag( $ref, $value ): void
	{
		$this->cache->set( $value, $ref );
	}

	public function getRoot()
	{
		return reset( $this->context );
	}

	public function getIndex(): int
	{
		return $this->current;
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

	public function getParent(): ?ExecuteContext
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

	public function getCurrentRoutine(): ?RoutineModel
	{
		return $this->getCurrent( 'routine' );
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
		$this->getExecuteService()->executeEvent( $this, 'flow' );
	}

	public function startRoutine( RoutineModel $routine )
	{
		$this->setCurrent( $routine, 'routine' );
		$this->getExecuteService()->executeEvent( $this, 'routine' );
	}

	public function startTask( TaskModel $task )
	{
		$this->setCurrent( $task, 'task' );
		$this->getExecuteService()->executeEvent( $this, 'task' );
	}

	public function endFlow()
	{
		$this->setCurrent( null, 'flow' );
	}

	public function endRoutine()
	{
		$this->setCurrent( null, 'routine' );
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
		// @todo Unset current?
		$previous      = $this->getCurrent( '_prev' );
		$this->current = $previous;

		return $this->getCurrent();
	}

	public function descend( ?AutomationModel $automation, array $variables = [] ): ExecuteContext
	{
		return new static( $this->execute, $automation, $this, $variables );
	}

	public function ascend(): ?ExecuteContext
	{
		return $this->getParent() ?? $this;
	}

	public function getLogs(): array
	{
		return $this->logs;
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
	public function addLog( TraceLog|\Throwable|string $message, mixed $info = null ): void
	{
		if ( ! $message instanceof TraceLog ) {
			$message = TraceLog::create( $message, TraceLogType::LOG, TraceContext::create( $this ) );
		}

		if ( $info ) {
			$message->setInfo( $info );
		}

		// Update parent logs.
		$this->getParent()?->addLog( $message );

		if ( ! $this->getParent() ) {
			$this->getTrace()?->addLog( $message );
		}

		$this->logs[] = $message;
	}

	/**
	 * @param  \Throwable|string  $message
	 * @param  mixed              $info
	 *
	 * @return void
	 */
	public function addError( TraceLog|\Throwable|string $message, mixed $info = null ): void
	{
		$exit = ( $message instanceof \ErrorException ) ? $message : null;

		if ( ! $message instanceof TraceLog ) {
			$message = TraceLog::create( $message, TraceLogType::ERROR, TraceContext::create( $this ) );
		}

		if ( $info ) {
			$message->setInfo( $info );
		}

		// Update parent errors.
		$this->getParent()?->addError( $message );

		if ( ! $this->getParent() ) {
			$this->getTrace()?->addError( $message );
		}

		$this->errors[] = $message;

		if ( $exit ) {
			$this->getTrace()?->store();
			throw $exit;
		}
	}

	public function offsetExists( mixed $offset ): bool
	{
		if ( is_int( $offset ) ) {
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
		if ( is_int( $offset ) ) {
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
		if ( is_int( $offset ) ) {
			parent::offsetSet( $offset, $value );
		}
		$this->setCurrent( $value, $offset );
	}

	public function offsetUnset( mixed $offset ): void
	{
		if ( is_int( $offset ) ) {
			parent::offsetUnset( $offset );
		}
		unset( $this->context[ $this->getIndex() ][ $offset ] );
	}
}
