<?php

namespace SyncEngine\Model;

use Symfony\Component\Filesystem\Filesystem;
use SyncEngine\Entity\Trace;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Service\Data\ResourceData;
use SyncEngine\Service\Data\TraceData;
use SyncEngine\Service\Trace\TraceLog;

/**
 * @method int getId()
 * @method setId( int $id )
 * @method string getName()
 * @method setName( string $name )
 * @method Trace getEntity()
 */
class TraceModel extends EntityModel
{
	/**
	 * @var ResourceData<TraceData>
	 */
	private ResourceData $traceData;
	private int $iteration = 0;
	private TraceStatus $status;

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

	public function addLog( $message, $type = 'Log' ): static
	{
		$message = TraceLog::create( $message, $type );

		$trace = $this->getCurrentTrace();

		// Errors and responses can be large.
		if ( $message->getException() || $message->getResponse() || $message->getData() ) {

			$type = $trace->createUniqueKey(
				$type . ': ' . microtime(true),
				$trace->getCurrentTraces()
			);

			$message->set( $this->storeLog( $type, $message->normalize() ) );
		}

		$trace->addLog( $message, $type );

		return $this;
	}

	public function addError( $message ): static
	{
		$this->addLog( $message, 'Error' );
		$this->setStatus( TraceStatus::FAILED );

		return $this;
	}

	public function enterTrace( $model, $type = '' ): static
	{
		$this->getCurrentTrace()->enterTrace( $model, $type );

		return $this;
	}

	public function leaveTrace( $model ): static
	{
		$this->getCurrentTrace()->leaveTrace( $model );

		return $this;
	}

	public function resetTraversal(): static
	{
		$this->getCurrentTrace()->resetTraversal();

		return $this;
	}

	public function getStatus(): ?TraceStatus
	{
		if ( isset( $this->status ) ) {
			return $this->status;
		}

		if ( ! $this->hasEntity() ) {
			return null;
		}

		$this->status = TraceStatus::create( $this->getEntity()->getStatus() );

		return $this->status;
	}

	public function setStatus( TraceStatus $status ): static
	{
		$this->status = $status;

		if ( $this->hasEntity() ) {
			$this->getEntity()->setStatus( $status->value );
		}

		return $this;
	}

	public function isStatus( TraceStatus $status ): bool
	{
		return $this->getStatus() === $status;
	}

	public function isFinished(): bool
	{
		return match ( $this->getStatus() ) {
			TraceStatus::FAILED, TraceStatus::SUCCESS, TraceStatus::STOPPED => true,
			default => false,
		};
	}

	public function start( ?AutomationModel $automation = null ): static
	{
		if ( ! $this->getCreated() ) {
			$this->setCreated( new \DateTimeImmutable() );
		}

		$this->setStatus( TraceStatus::RUNNING );

		$iterator = [];
		if ( $automation ) {
			$iterator = $automation->getIterator();

			$this->register( $automation );
		}

		$this->iteration = $iterator['current'] ?? 0;

		$trace = $this->getCurrentTrace();

		$trace->resetTraversal();

		$trace->set( microtime( true ), 'time_start' );

		if ( $iterator ) {
			$trace->set( $iterator, 'iterator' );
		}

		return $this;
	}

	public function end( $reset = false ): static
	{
		if ( $reset || ! $this->getCurrentTrace()->has( 'time_end' ) ) {
			$this->getCurrentTrace()->set( microtime( true ), 'time_end' );
		}

		if ( ! $this->isFinished() ) {
			$this->setStatus( TraceStatus::STOPPED );
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
		$max = (int) $this->getParameter( 'max_traces' ) ?? 10;

		$count = $automation->getTraces()->count();
		if ( $max < $count ) {
			// Traces are ordered by created data (DESC).
			$remove = $automation->getTraces()->slice( $max );
			foreach ( $remove as $trace ) {
				TraceModel::create( $trace )->removeTraceFiles();
				$automation->removeTrace( $trace );
			}
		}
	}

	public function store( AutomationModel $automation ): static
	{
		// Link trace to automation.
		$automation->addTrace( $this->getEntity() );

		$files = $this->getTraceFiles();

		$this->storeTraceFileContent( $this->iteration, $this->end()->getCurrentTrace()->normalize() );
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
				$this->traceData[ $iteration ] = new TraceData( $this->fetchTraceFileContent( $file ) );
			}
			$fullTrace[ $iteration ] = $this->traceData[ $iteration ]->get();
		}

		return $fullTrace;
	}

	public function getCurrentTrace(): TraceData
	{
		if ( ! isset( $this->traceData ) ) {
			$this->traceData = new ResourceData();
		}

		$iteration = $this->iteration;

		if ( ! isset( $this->traceData[ $iteration ] ) ) {

			$files = $this->getTraceFiles();

			if ( isset( $files[ $iteration ] ) ) {
				$this->traceData[ $iteration ] = new TraceData( $this->fetchTraceFileContent( $files[ $iteration ] ) );
			} else {
				$this->traceData[ $iteration ] = new TraceData();
			}
		}

		return $this->traceData[ $iteration ];
	}

	public function removeTraceFiles(): void
	{
		( new Filesystem() )->remove( $this->getTraceDir() );
	}

	protected function storeTraceFileContent( $iteration, $trace ): void
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

	public function getTraceFiles( bool $path = false ): array
	{
		$data = $this->getEntity()->getTrace();

		$files = $data['files'] ?? [];

		if ( $path ) {
			$files = array_map( [ $this, 'getTraceFilePath' ], $files );
		}

		return $files;
	}

	protected function storeLog( string $name, array $message ): array
	{
		if ( ! $this->getId() ) {
			// Trace not initialized yet.
			return $message;
		}

		$dir = $this->getTraceLogDir( $this->iteration );

		$file = str_replace( [ ':', '.' ], '', $name ) . '.json';

		( new Filesystem() )->dumpFile( $dir . $file, json_encode( $message ) );

		// Remove all potentially large keys.
		$message = array_filter( $message, 'is_string' );
		$message['file'] = $file;

		return $message;
	}

	public function getStoredLog( array|string $log, ?int $iteration = null )
	{
		$dir = $this->getTraceLogDir( $iteration ?? $this->iteration );

		if ( is_array( $log ) ) {
			$log = $log['file'] ?? '';
		}

		$file = $dir . $log;

		if ( ! $log || ! file_exists( $file ) ) {
			return null;
		}

		return json_decode( file_get_contents( $file ), true );
	}

	public function getTraceLogDirs(): array
	{
		$files = $this->getTraceFiles();

		return array_map( [ $this, 'getTraceLogDir' ], $files );
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
		return $this->getTraceIdentifier( $iteration ) . '.json';
	}

	public function getTraceLogDir( string|int $traceIdentifier ): string
	{
		if ( is_numeric( $traceIdentifier ) ) {
			$traceIdentifier = $this->getTraceIdentifier( $traceIdentifier );
		}
		return $this->getTraceDir() . basename( $traceIdentifier, '.json' ) . '/';
	}

	public function getTraceIdentifier( $iteration = 0 ): string
	{
		return 'trace_' . $this->getId() . '_iteration_' . $iteration;
	}

	public function getTraceDirname(): string
	{
		return 'trace_' . $this->getId();
	}

	public function getTraceDir(): string
	{
		$fs = new Filesystem();
		$dir = $this->getParameter('dir.root') . '/var/trace/' . $this->getAutomation()->getId() . '/' . $this->getTraceDirname();

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
