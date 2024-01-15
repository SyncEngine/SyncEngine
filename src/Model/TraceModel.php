<?php

namespace SyncEngine\Model;

use SyncEngine\Entity\Trace;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Service\ResourceData;

/**
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method TraceStatus getStatus()
 * @method setStatus( TraceStatus $status )
 */
class TraceModel extends EntityModel
{
	private ResourceData $trace;
	private int $current = 0;
	private array $traverse = [];

	public function __construct( ?Trace $trace = null )
	{
		parent::__construct( $trace );
	}

	public function addLog( $message ): void
	{
		$key = implode( '.trace.', $this->traverse );

		$trace = $this->getTrace()->get( $key, [] );

		if ( ! isset( $trace['trace'] ) ) {
			$trace['trace'] = [];
		}
		$trace['trace'][ 'Log: ' . time() ] = $message;

		ksort( $trace );
		$this->getTrace()->set( $trace, $key );
	}

	public function addError( $message ): void
	{
		$key = implode( '.trace.', $this->traverse );

		$trace = $this->getTrace()->get( $key, [] );

		if ( ! isset( $trace['trace'] ) ) {
			$trace['trace'] = [];
		}
		$trace['trace'][ 'Error: ' . time() ] = $message;

		ksort( $trace );
		$this->getTrace()->set( $trace, $key );

		$this->setFailed();
	}

	public function enterTrace( $model ): void
	{
		if ( is_array( $model ) ) {
			$ref = $model['_ref']; // @todo Validate item.
			$name = $model['_label'] ?? '';
			$type = $model['_class'] ?? '';
		} elseif ( is_object( $model ) ) {
			$ref = $model->getRef();
			$name = $model->getName();
			if ( $model instanceof EntityModel ) {
				$type = $model::getEntityClass();
			} else {
				$type = $model->getClassLocator();
			}
		} else {
			$ref = (string) $model;
			$name = '';
			$type = '';
		}

		// Check if it is the same loop.
		$isCurrent = $ref === end( $this->traverse );

		$key = implode( '.trace.', $this->traverse );
		$current = $this->getTrace()->get( $key );

		if ( $isCurrent ) {
			if ( ! isset( $current['count'] ) ) {
				$current['count'] = 1;
			}
			$current['count']++;
			ksort( $current );
			$this->getTrace()->set( $current, $key );

			return;
		}

		// Make sure a trace exists.
		if ( ! isset( $current['trace'] ) ) {
			$current['trace'] = [];
			$this->getTrace()->set( $current, $key );
		}

		$this->traverse[] = $ref;

		$key = implode( '.trace.', $this->traverse );
		$current = $this->getTrace()->get( $key, [] );

		if ( ! empty( $current ) ) {
			if ( ! isset( $current['count'] ) ) {
				$current['count'] = 1;
			}
			$current['count']++;
			ksort( $current );
			$this->getTrace()->set( $current, $key );

			return;
		}

		$info = '';
		if ( $name !== $ref ) {
			$info = $name;
		}
		if ( $type ) {
			$info = $info ? $info . ' (' . $type . ')' : $type;
		}
		if ( $info ) {
			$current['info'] = $info;
		}

		ksort( $current );
		$this->getTrace()->set( $current, $key );
	}

	public function leaveTrace( $model ): void
	{
		if ( is_array( $model ) ) {
			$ref = $model['_ref'];
		} elseif ( is_object( $model ) ) {
			$ref = $model->getRef();
		} else {
			$ref = (string) $model;
		}

		if ( $ref === end( $this->traverse ) ) {
			array_pop( $this->traverse );
		}
	}

	public function resetTraveral(): self
	{
		$this->traverse = [ $this->current ];

		return $this;
	}

	public function setSuccess(): static
	{
		$this->setStatus( TraceStatus::SUCCESS );

		return $this;
	}

	public function setRunning(): static
	{
		$this->setStatus( TraceStatus::RUNNING );

		return $this;
	}

	public function setFailed(): static
	{
		$this->setStatus( TraceStatus::FAILED );

		return $this;
	}

	public function addTrace()
	{
		$this->resetTraveral();
	}

	public function getTrace(): ResourceData
	{
		if ( ! isset( $this->trace ) ) {
			$this->trace = new ResourceData( $this->getEntity()->getTrace() );
		}

		return $this->trace;
	}

	public function start( array $iterator = [] ): self
	{
		if ( ! $this->getCreated() ) {
			$this->setCreated( new \DateTimeImmutable() );
		}

		$this->current = $iterator['index'] ?? 0;

		$this->resetTraveral();

		if ( $iterator ) {
			$this->addLog( $iterator );
		}

		return $this;
	}

	public function load( AutomationModel $automation, Trace|int $trace ): self
	{
		if ( ! $trace instanceof Trace ) {
			$trace = static::getRepository()->findOneBy( [ 'id' => $trace, 'automation' => $automation->getId() ] );
		}

		if ( ! $trace instanceof Trace ) {
			throw new \Exception( 'Cannot find trace: ' . $trace );
		}

		$this->entity = $trace;

		return $this;
	}

	public function store( AutomationModel $automation ): self
	{
		$this->setTrace( $this->getTrace()->get() );

		$automation->addTrace( $this->getEntity() );

		$this->persist( true );

		// Limit number of traces.
		$max = $this->getParameter( 'max_traces' ) ?? 10;

		$count = $automation->getTraces()->count();
		if ( $max < $count ) {
			$remove = $automation->getTraces()->slice( 0, $count - $max );
			foreach ( $remove as $trace ) {
				$automation->removeTrace( $trace );
			}
		}

		return $this;
	}

	public function getAutomation(): ?AutomationModel
	{
		if ( ! $this->hasEntity() ) {
			return null;
		}
		return AutomationModel::create( $this->entity->getAutomation() );
	}

	public static function getEntityClass(): string
	{
		return Trace::class;
	}
}
