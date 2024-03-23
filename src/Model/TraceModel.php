<?php

namespace SyncEngine\Model;

use Symfony\Component\Filesystem\Filesystem;
use SyncEngine\Entity\Trace;
use SyncEngine\Model\Abstract\AbstractModel;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Service\ResourceData;

/**
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method string getStatus()
 * @method setStatus( string $status )
 * @method Trace getEntity()
 */
class TraceModel extends EntityModel
{
	const SUCCESS = 'success';
	const RUNNING = 'running';
	const FAILED = 'failed';

	private ResourceData $traceData;
	private int $iteration = 0;
	private array $traverse = [];

	public function __construct( ?Trace $trace = null )
	{
		parent::__construct( $trace );
	}

	public static function load( AutomationModel $automation, Trace|int $trace ): static
	{
		if ( ! $trace instanceof Trace ) {
			$trace = static::getRepository()->findOneBy( [ 'id' => $trace, 'automation' => $automation->getId() ] );
		}

		if ( ! $trace instanceof Trace ) {
			throw new \Exception( 'Cannot find trace: ' . $trace );
		}

		return static::create( $trace );
	}

	public function addLog( $message ): static
	{
		$key = $this->getTraverseKey();

		$traceData = $this->getCurrentTrace()->get( $key, [] );

		if ( ! isset( $traceData['trace'] ) ) {
			$traceData['trace'] = [];
		}
		$traceData['trace'][ 'Log: ' . microtime(true) ] = $message;

		ksort( $traceData );
		$this->getCurrentTrace()->set( $traceData, $key );

		return $this;
	}

	public function addError( $message ): static
	{
		$key = $this->getTraverseKey();

		$traceData = $this->getCurrentTrace()->get( $key, [] );

		if ( ! isset( $traceData['trace'] ) ) {
			$traceData['trace'] = [];
		}
		$traceData['trace'][ 'Error: ' . microtime(true) ] = $message;

		ksort( $traceData );
		$this->getCurrentTrace()->set( $traceData, $key );

		$this->setFailed();

		return $this;
	}

	public function enterTrace( $model, $type = '' ): static
	{
		if ( is_array( $model ) ) {
			$ref  = $model['_ref']; // @todo Validate item.
			$name = $model['_label'] ?? '';
			$type = ( $type ? $type . ':' : '' ) . $model['_class'] ?? '';
		} elseif ( is_object( $model ) ) {
			$ref  = $model->getRef();
			$name = $model->getName();
			if ( $model instanceof AbstractModel ) {
				$type = $model::getModelName();
			} else {
				$type = $model->getClassLocator();
			}
		} else {
			$ref  = (string) $model;
			$name = $ref;
		}

		// Check if it is the same loop.
		$isCurrent = $ref === end( $this->traverse );

		$key = $this->getTraverseKey();
		$current = $this->getCurrentTrace()->get( $key );

		if ( $isCurrent ) {
			if ( ! isset( $current['count'] ) ) {
				$current['count'] = 1;
			}
			$current['count']++;
			ksort( $current );
			$this->getCurrentTrace()->set( $current, $key );

			return $this;
		}

		// Make sure a trace exists.
		if ( ! empty( $this->traverse ) && ! isset( $current['trace'] ) ) {
			$current['trace'] = [];
			$this->getCurrentTrace()->set( $current, $key );
		}

		$this->traverse[] = $ref;

		$key = $this->getTraverseKey();
		$current = $this->getCurrentTrace()->get( $key, [] );

		if ( ! empty( $current ) ) {
			if ( ! isset( $current['count'] ) ) {
				$current['count'] = 1;
			}
			$current['count']++;
			ksort( $current );
			$this->getCurrentTrace()->set( $current, $key );

			return $this;
		}

		$current['name'] = $name;
		$current['type'] = $type;
		$current['ref']  = $ref;
		$current['time_enter'] = microtime(true);
		$current['time_leave'] = false;

		ksort( $current );
		$this->getCurrentTrace()->set( $current, $key );

		return $this;
	}

	public function leaveTrace( $model ): static
	{
		if ( is_array( $model ) ) {
			$ref = $model['_ref'];
		} elseif ( is_object( $model ) ) {
			$ref = $model->getRef();
		} else {
			$ref = (string) $model;
		}

		if ( $ref === end( $this->traverse ) ) {

			$key = $this->getTraverseKey();
			$current = $this->getCurrentTrace()->get( $key, [] );
			if ( ! empty( $current ) ) {
				$current['time_leave'] = microtime(true);
				$this->getCurrentTrace()->set( $current, $key );
			}

			array_pop( $this->traverse );
		}

		return $this;
	}

	public function getTraverseKey(): string
	{
		if ( $this->traverse ) {
			return 'trace.' . implode( '.trace.', $this->traverse );
		}

		return 'trace';
	}

	public function resetTraveral(): static
	{
		$this->traverse = [];//[ $this->iteration ];

		return $this;
	}

	public function setSuccess(): static
	{
		$this->setStatus( static::SUCCESS );

		return $this;
	}

	public function setRunning(): static
	{
		$this->setStatus( static::RUNNING );

		return $this;
	}

	public function setFailed(): static
	{
		$this->setStatus( static::FAILED );

		return $this;
	}

	public function start( ?AutomationModel $automation = null ): static
	{
		if ( ! $this->getCreated() ) {
			$this->setCreated( new \DateTimeImmutable() );
		}

		$this->setRunning();

		$iterator = [];
		if ( $automation ) {
			$iterator = $automation->getIterator();

			$this->register( $automation );
		}

		$this->iteration = $iterator['current'] ?? 0;

		$this->resetTraveral();

		$this->getCurrentTrace()->set( microtime( true ), 'time_start' );

		if ( $iterator ) {
			$this->getCurrentTrace()->set( $iterator, 'iterator' );
		}

		return $this;
	}

	public function end( $reset = false ): static
	{
		if ( $reset || ! $this->getCurrentTrace()->has( 'time_end' ) ) {
			$this->getCurrentTrace()->set( microtime( true ), 'time_end' );
		}

		return $this;
	}

	public function register( AutomationModel $automation )
	{
		// Register trace to automation.
		$automation->addTrace( $this->getEntity() );

		// Persist trace to generate ID.
		if ( ! $this->getId() ) {
			$this->persist( true );
		}

		// Limit number of traces.
		$max = $this->getParameter( 'max_traces' ) ?? 10;

		$count = $automation->getTraces()->count();
		if ( $max < $count ) {
			$remove = $automation->getTraces()->slice( 0, $count - $max );
			foreach ( $remove as $trace ) {
				$this->removeTraceFiles( $trace );
				$automation->removeTrace( $trace );
			}
		}
	}

	public function store( AutomationModel $automation ): static
	{
		// Link trace to automation.
		$automation->addTrace( $this->getEntity() );

		$files = $this->getTraceFiles();

		$this->storeTraceFileContent( $this->iteration, $this->end()->getCurrentTrace()->get() );
		$files[ $this->iteration ] = $this->getTraceFilename( $this->iteration );

		/*foreach ( $this->traceData->get() as $iteration => $data ) {
			if ( is_iterable( $data ) ) {
				// @todo Remove current file?
				// Override with new file.
				$data = ( $data instanceof ResourceData ) ? $data->get() : $data;
				$this->storeTraceFileContent( $iteration, $data );
				$files[ $iteration ] = $this->getTraceFilename( $iteration );
			}
		}*/

		$this->setTrace( [ 'files' => $files ] );

		$this->save( true );

		return $this;
	}

	public function getAutomation(): ?AutomationModel
	{
		if ( ! $this->hasEntity() ) {
			return null;
		}
		return AutomationModel::create( $this->entity->getAutomation() );
	}

	public function getFullTrace(): array
	{
		if ( ! isset( $this->traceData ) ) {
			$this->traceData = new ResourceData();
		}

		$fullTrace = $this->getEntity()?->getTrace() ?? [];

		foreach ( $this->getTraceFiles() as $iteration => $file ) {
			if ( ! isset( $this->traceData[ $iteration ] ) ) {
				$this->traceData[ $iteration ] = new ResourceData( $this->fetchTraceFileContent( $file ) );
			}
			$fullTrace[ $iteration ] = $this->traceData[ $iteration ]->get();
		}

		return $fullTrace;
	}

	public function getCurrentTrace(): ResourceData
	{
		if ( ! isset( $this->traceData ) ) {
			$this->traceData = new ResourceData();
		}

		$iteration = $this->iteration;

		if ( ! isset( $this->traceData[ $iteration ] ) ) {

			$files = $this->getTraceFiles();

			if ( isset( $files[ $iteration ] ) ) {
				$this->traceData[ $iteration ] = new ResourceData( $this->fetchTraceFileContent( $files[ $iteration ] ) );
			} else {
				$this->traceData[ $iteration ] = new ResourceData();
			}
		}

		return $this->traceData[ $iteration ];
	}

	public function removeTraceFiles( Trace $trace ): void
	{
		( new Filesystem() )->remove( $this->getTraceFiles( true, $trace ) );
	}

	public function storeTraceFileContent( $iteration, $trace ): void
	{
		( new Filesystem() )->dumpFile( $this->getTraceFilePath( $iteration ), json_encode( $trace ) );
	}

	public function fetchTraceFileContent( $filename_or_iteration ): array
	{
		$file = $this->getTraceFilePath( $filename_or_iteration );

		if ( ! file_exists( $file ) ) {
			return [];
		}

		return json_decode( file_get_contents( $file ), true ) ?? [];
	}

	public function getTraceFiles( bool $path = false, ?Trace $trace = null ): array
	{
		if ( ! $trace ) {
			$trace = $this->getEntity();
		}
		$data = $trace->getTrace();

		$files = $data['files'] ?? [];

		if ( $path ) {
			$files = array_map( [ $this, 'getTraceFilePath' ], $files );
		}

		return $files;
	}

	public function getTraceFilePath( $filename_or_iteration ): string
	{
		$filename = $filename_or_iteration;

		if ( is_numeric( $filename ) ) {
			$filename = $this->getTraceFilename( $filename );
		}

		return $this->getTraceDir() . $filename;
	}

	public function getTraceFilename( $iteration = 0 ): string
	{
		return 'trace_' . $this->getId() . '_iteration_' . $iteration . '.json';
	}

	public function getTraceDir(): string
	{
		$fs = new Filesystem();
		$dir = $this->getParameter('dir.root') . '/var/trace/' . $this->getAutomation()->getId();

		if ( ! $fs->exists( $dir ) ) {
			$fs->mkdir( $dir );
		}

		return $dir . '/';
	}

	protected function getTrace(): array
	{
		return $this->getFullTrace();
	}

	protected function setTrace( array $trace ): void
	{
		if ( $this->hasEntity() ) {
			$this->getEntity()->setTrace( $trace );
		}
	}

	public static function getEntityClass(): string
	{
		return Trace::class;
	}
}
