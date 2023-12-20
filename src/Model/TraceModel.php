<?php

namespace SyncEngine\Model;

use SyncEngine\Entity\Trace;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Service\ResourceData;

/**
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getStatus()
 * @method setStatus( string $description )
 * @method Trace getEntity()
 */
class TraceModel extends EntityModel
{
	private ResourceData $trace;
	private array $current = [];

	public function __construct( ?Trace $trace = null )
	{
		parent::__construct( $trace );
	}

	public function addLog( $message ): void
	{
		$key = implode( '.trace.', $this->current );

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
		$key = implode( '.trace.', $this->current );

		$trace = $this->getTrace()->get( $key, [] );

		if ( ! isset( $trace['trace'] ) ) {
			$trace['trace'] = [];
		}
		$trace['trace'][ 'Error: ' . time() ] = $message;

		ksort( $trace );
		$this->getTrace()->set( $trace, $key );
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
		$isCurrent = $ref === end( $this->current );

		$key = implode( '.trace.', $this->current );
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

		$this->current[] = $ref;

		$key = implode( '.trace.', $this->current );
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

		if ( $ref === end( $this->current ) ) {
			array_pop( $this->current );
		}
	}

	public function resetTrace(): self
	{
		$this->current = [];

		return $this;
	}

	public function getTrace(): ResourceData
	{
		if ( ! isset( $this->trace ) ) {
			$this->trace = new ResourceData( $this->getEntity()->getTrace() );
		}

		return $this->trace;
	}

	public function start(): self
	{
		if ( ! $this->hasEntity() ) {
			$this->init();
		}

		return $this;
	}

	public function init( AutomationModel $automation = null, $trace = null ): self
	{
		if ( $trace ) {
			$trace = $automation->getTraces()->get( $trace );
		}

		if ( ! $trace ) {
			$trace = new Trace();
		}

		$this->trace = $trace;

		return $this;
	}

	public function store( AutomationModel $automation ): self
	{
		$automation->addTrace( $this->getEntity() );

		$this->clean( $automation );

		return $this;
	}

	public function clean( AutomationModel $automation ): self
	{
		$max = $this->getContainer()->get( 'parameter_bag' )->get( 'max_traces' ) ?? 10;

		$count = $automation->getTraces()->count();
		if ( $max > $count ) {
			$remove = $automation->getTraces()->slice( 0, $count - $max );
			foreach ( $remove as $trace ) {
				$automation->removeTrace( $trace );
			}
		}

		return $this;
	}

	public static function getEntityClass(): string
	{
		return Trace::class;
	}
}
