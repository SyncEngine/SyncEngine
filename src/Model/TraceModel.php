<?php

namespace SyncEngine\Model;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use SyncEngine\Entity\Trace;
use SyncEngine\Exception\ExecuteStopException;
use SyncEngine\Exception\InvalidException;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\Trace\TraceData;
use SyncEngine\Service\Trace\TraceLog;
use SyncEngine\Structure\Data\ResourceData;

/**
 * @extends EntityModel<Trace>
 *
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
	private ?TraceStatus $status;
	private ?AutomationModel $automation;
	private ?int $iteration = null;
	private ?int $lastAutoSave = 1;
	private ?int $killTimestamp = null;
	private bool $isRegistered = false;
	private bool $hasErrors;
	private bool $iteratorEnded = false;

	public function __construct( ?Trace $trace = null )
	{
		parent::__construct( $trace );
	}

	public function update( $flush = false, ?EntityManagerInterface $entityManager = null ): void
	{
		if ( $this->hasEntity() ) {
			$this->getEntity()->setModified( new \DateTimeImmutable() );
		}

		parent::update( $flush, $entityManager );
	}

	public function persist( $flush = false, ?EntityManagerInterface $entityManager = null ): void
	{
		$entity = $this->getEntity();

		if ( ! $entity->getCreated() ) {
			$entity->setCreated( new \DateTimeImmutable() );
		}
		$entity->setModified( new \DateTimeImmutable() );

		parent::persist( $flush, $entityManager );
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

		$this->progress();

		return $this;
	}

	public function addError( $message ): static
	{
		$this->hasErrors = true;
		$this->addLog( $message, 'Error' );

		return $this;
	}

	public function enterTrace( $model, $type = '' ): static
	{
		$this->getCurrentTrace()->enterTrace( $model, $type );

		$this->progress();

		return $this;
	}

	public function leaveTrace( $model ): static
	{
		$this->getCurrentTrace()->leaveTrace( $model );

		$this->progress();

		return $this;
	}

	public function resetTraversal(): static
	{
		$this->getCurrentTrace()->resetTraversedNodes();

		return $this;
	}

	/**
	 * In-memory file-pointer used by getCurrentTrace() / store().
	 */
	public function getIteration(): int
	{
		return $this->iteration ?? 0;
	}

	/**
	 * The persisted batch-iteration counter for this individual run.
	 * During a live session the value set by setCurrentIteration() / nextIteration() takes
	 * precedence; on a fresh load (e.g. a new batch worker) it falls back to the counter
	 * that was written into the trace JSON blob by the previous store() call.
	 */
	public function getCurrentIteration(): int
	{
		if ( $this->iteration === null ) {
			$this->iteration = (int) $this->getTraceData( 'iteration' );
		}

		return $this->iteration;
	}

	/**
	 * Update the live session counter and file-pointer.
	 */
	public function setCurrentIteration( int $iteration ): void
	{
		$this->iteration = $iteration;
	}

	/**
	 * Advance to the next batch. Should be called externally before processing each batch.
	 */
	public function nextIteration(): void
	{
		$this->setCurrentIteration( $this->getCurrentIteration() + 1 );
	}

	/**
	 * Reset the batch counter (end of iteration cycle).
	 */
	public function resetIterator(): void
	{
		$this->setCurrentIteration( 0 );
	}

	/**
	 * Mark the iterator as finished so store() persists the reset counter.
	 */
	public function endIterator(): void
	{
		$this->iteratorEnded = true;
	}

	public function isIteratorEnded(): bool
	{
		return $this->iteratorEnded;
	}

	public function getStatus(): ?TraceStatus
	{
		if ( isset( $this->status ) ) {
			return $this->status;
		}

		$status = $this->getEntity()?->getStatus();

		if ( $status ) {
			$this->status = TraceStatus::create( $status );
		}

		return $this->status ?? null;
	}

	public function setStatus( TraceStatus $status ): static
	{
		$this->status = $status;

		$this->getEntity()?->setStatus( $status->value );

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

	public function hasErrors(): bool
	{
		if ( ! isset( $this->hasErrors ) ) {
			$this->hasErrors = ! empty( $this->getTraceData( 'hasErrors' ) );
		}
		return $this->hasErrors;
	}

	public function start( ?ExecuteContext $context = null ): static
	{
		if ( ! $this->getCreated() ) {
			$this->setCreated( new \DateTimeImmutable() );
		}

		$this->setStatus( TraceStatus::RUNNING );

		$request    = null;
		$automation = null;
		if ( $context ) {
			$automation = $context->getAutomation();
			if ( $automation ) {
				$this->register( $automation );
			}

			if ( $context->getRequest() ) {
				$request = [
					'query'  => $context->getRequestQuery(),
					'params' => $context->getRequestParams(),
				];
			}
		}

		// Non-iterator runs always write to iteration 0; iterator runs advance via nextIteration().
		if ( $automation && ! $automation->hasIterator() ) {
			$this->resetIterator();
		}

		$trace = $this->getCurrentTrace();

		$trace->resetTraversedNodes();

		if ( $automation && $automation->hasIterator() ) {
			$trace->set( $automation->getIteration( $this ), 'iteration' );
		}

		if ( $request ) {
			$trace->set( $request, 'request' );
		}

		$trace->set( microtime( true ), 'time_start' );
		$trace->set( memory_get_usage(), 'memory_start' );
		memory_reset_peak_usage();

		return $this;
	}

	public function setQueuedRequest( array $params = [], array $query = [] ): static
	{
		$trace = (array) ( $this->getEntity()?->getTrace() ?? [] );
		$trace['queue'] = [
			'params'    => $params,
			'query'     => $query,
			'createdAt' => time(),
		];

		$this->setTrace( $trace );

		return $this;
	}

	/**
	 * Returns queued request payload and clears it from trace storage.
	 *
	 * @return array{params: array, query: array}
	 */
	public function pullQueuedRequest(): array
	{
		$trace = (array) ( $this->getEntity()?->getTrace() ?? [] );
		$queue = (array) ( $trace['queue'] ?? [] );

		unset( $trace['queue'] );
		$this->setTrace( $trace );
		$this->save( true );

		return [
			'params' => (array) ( $queue['params'] ?? [] ),
			'query'  => (array) ( $queue['query'] ?? [] ),
		];
	}

	public function end( $reset = false ): static
	{
		$this->resetTraversal();

		$trace = $this->getCurrentTrace();

		if ( $reset || ! $trace->has( 'time_end' ) ) {
			$trace->set( microtime( true ), 'time_end' );
		}

		$peak = memory_get_peak_usage();
		$trace->set( $peak, 'memory_peak_total' );
		$trace->set( memory_get_usage(), 'memory_end' );
		$trace->set( $peak - (int) $trace->get( 'memory_start' ), 'memory_peak' );

		return $this;
	}

	public function finish(): static
	{
		$this->end();

		if ( $this->hasErrors() ) {
			$this->setStatus( TraceStatus::FAILED );
		} elseif ( ! $this->isFinished() ) {
			$this->setStatus( TraceStatus::STOPPED );
		}

		return $this;
	}

	public function register( AutomationModel $automation ): static
	{
		if ( isset( $this->automation ) && $this->automation->getTraces()->contains( $this->getEntity() ) ) {
			if ( $this->automation === $automation ) {
				return $this;
			}
			throw new InvalidException( 'Trace already registered' );
		}

		// Register trace to automation.
		$this->setAutomation( $automation );
		$automation->addTrace( $this->getEntity() );

		/**
		 * Persist trace to generate ID and persist the link to the automation.
		 *
		 * This needs to be done BEFORE max trace handling because you cannot persist a new entity AND remove an existing.
		 * "A managed+dirty entity SyncEngine\Entity\Trace@### can not be scheduled for insertion."
		 */
		$this->persist( ! $this->getId() );

		// Limit number of traces by user setting.
		$max = $automation->getMaxTraces();

		// Keep in-flight queue/schedule traces; only prune finished/history traces.
		$traces = $automation->getTraces()->filter( function ( Trace $trace ) {
			return ! in_array( $trace->getStatus(), [ TraceStatus::SCHEDULED->value, TraceStatus::QUEUED->value ], true );
		} );

		// Remove traces above limit that are finished.
		if ( $max < $traces->count() ) {
			// Traces are ordered by created data (DESC) so slice is safe to use.
			$remove = $traces->slice( $max );
			foreach ( $remove as $trace ) {
				TraceModel::create( $trace )->removeTraceFiles();
				$automation->removeTrace( $trace );
			}
		}

		$this->isRegistered = true;

		return $this;
	}

	public function isRegistered(): bool
	{
		return ! empty( $this->isRegistered ) && isset( $this->automation );
	}

	public function delete( $flush = false, ?EntityManagerInterface $entityManager = null ): bool
	{
		$this->removeTraceFiles();
		return parent::delete( $flush, $entityManager );
	}

	public function disableAutoSave(): static
	{
		$this->lastAutoSave = null;

		return $this;
	}

	public function enableAutoSave(): static
	{
		$this->lastAutoSave = 1;

		return $this;
	}

	/**
	 * Store current state each second.
	 * @return static
	 */
	public function progress(): static
	{
		$this->checkKillSwitch();

		if ( ! $this->lastAutoSave || $this->killTimestamp ) {
			// Autosave disabled.
			return $this;
		}

		$time = time();
		if ( $this->lastAutoSave < $time ) {
			$this->lastAutoSave = $time;
			if ( $this->getAutomation() ) {
				$this->store();
			}
		}

		return $this;
	}

	public function checkKillSwitch(): static
	{
		static $killSwitch;
		if ( $this->killTimestamp ) {
			if ( $this->killTimestamp > time() + 1 ) {
				throw new ExecuteStopException( 'Killed already.' );
			}
			return $this;
		}
		if ( ! isset( $killSwitch ) ) {
			if ( ! $this->isRegistered() ) {
				return $this;
			}
			$killSwitch = $this->getTraceDir() . '.kill';
		}

		if ( ( new Filesystem() )->exists( $killSwitch ) ) {
			if ( ! $this->isStatus( TraceStatus::FAILED ) ) {
				$this->setStatus( TraceStatus::STOPPED );
			}
			$this->killTimestamp = time();
			throw new ExecuteStopException( 'Killed manually.' );
		}

		return $this;
	}

	public function kill(): static
	{
		( new Filesystem() )->touch( $this->getTraceDir() . '.kill' );

		return $this;
	}

	public function store(): static
	{
		if ( ! $this->isRegistered() ) {
			return $this;
		}

		$iteration = $this->getIteration();
		$this->storeTraceFileContent( $iteration, $this->getCurrentTrace()->normalize() );

		$files               = $this->getTraceFiles();
		$files[ $iteration ] = $this->getTraceFilename( $iteration );

		/*foreach ( $this->traceData->get() as $iteration => $data ) {
			if ( is_iterable( $data ) ) {
				// @todo Remove current file?
				// Override with new file.
				$data = ( $data instanceof ResourceData ) ? $data->get() : $data;
				$this->storeTraceFileContent( $iteration, $data );
				$files[ $iteration ] = $this->getTraceFilename( $iteration );
			}
		}*/

		/**
		 * @todo Store PID somewhere else to properly provide debug info an/or kill switch.
		 */
		$this->setTrace( [
			'files'     => $files,
			'hasErrors' => $this->hasErrors(),
			'processId' => @getmypid(),
			'iteration' => $this->isIteratorEnded() ? 0 : $this->getCurrentIteration(),
		] );

		$this->iteratorEnded = false;

		$this->save( true );

		return $this;
	}

	private function setAutomation( AutomationModel $automation ): static
	{
		$this->automation = $automation;
		if ( $this->hasEntity() ) {
			$this->getEntity()->setAutomation( $automation->getEntity() );
		}

		return $this;
	}

	public function getAutomation(): ?AutomationModel
	{
		if ( ! isset( $this->automation ) && $this->hasEntity() ) {
			$automation = $this->getEntity()->getAutomation();
			if ( $automation ) {
				$this->automation = AutomationModel::create( $automation );
			}
		}

		return $this->automation ?? null;
	}

	/**
	 * @throws \SyncEngine\Exception\InvalidOffsetException
	 * @return TraceData[]
	 */
	public function getFullTrace(): array
	{
		if ( ! isset( $this->traceData ) ) {
			$this->traceData = new ResourceData();
		}

		$fullTrace = $this->getTraceData() ?? [];

		$fullTrace[ $this->getIteration() ] = $this->getCurrentTrace();

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

		$iteration = $this->getIteration();

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
		$files = $this->getTraceData( 'files' ) ?? [];

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
		$message = array_filter( $message, 'is_scalar' );
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

		$folder = ( $this->getAutomation()?->getId() ?? '0' ) . '_' . $this->getAutomation()?->getRef();

		$dir = $this->getParameter('dir.trace') . '/' . $folder . '/' . $this->getTraceDirname();

		if ( ! $fs->exists( $dir ) ) {
			$fs->mkdir( $dir );
		}

		return $dir . '/';
	}

	/**
	 * Get the raw trace data from the entity object.
	 *
	 * @param  string|null  $key
	 *
	 * @return mixed
	 */
	private function getTraceData( ?string $key = null ): mixed
	{
		$data = $this->getEntity()?->getTrace();

		if ( ! $data ) {
			return null;
		}

		return $key ? $data[ $key ] ?? null : $data;
	}

	protected function getTrace(): array
	{
		return $this->getFullTrace();
	}

	protected function setTrace( array $trace ): void
	{
		$this->getEntity()?->setTrace( $trace );
	}

	public static function getEntityClass(): string
	{
		return Trace::class;
	}
}
