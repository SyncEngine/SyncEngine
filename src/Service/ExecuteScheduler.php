<?php

namespace SyncEngine\Service;

use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use SyncEngine\Messenger\MessengerManager;
use SyncEngine\Messenger\Message\AutomationBatch;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\Enum\AutomationMode;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Model\TraceModel;
use SyncEngine\Service\ExecuteScheduleResult;

class ExecuteScheduler
{
	public function __construct(
		private readonly MessageBusInterface $messageBus,
		private readonly MessengerManager    $messengerManager,
	) {}

	public function isSchedulerEnabled(): bool
	{
		return ! $this->messengerManager->isInternal() || $this->messengerManager->isEnabled();
	}

	public function canSchedule( AutomationModel $automation ): bool
	{
		return $this->isSchedulerEnabled() && $automation->canAcceptNewRequests();
	}

	public function schedule( AutomationModel $automation, ExecuteContext $context, array $stamps = [] ): ExecuteScheduleResult
	{
		// Default scheduling always creates a new async trace request.
		// Continuations must use scheduleNextIterationTrace() explicitly.
		return $this->scheduleNewTrace( $automation, $context, $stamps );
	}

	public function scheduleNewTrace( AutomationModel $automation, ExecuteContext $context, array $stamps = [] ): ExecuteScheduleResult
	{
		if ( ! $this->isSchedulerEnabled() ) {
			return ExecuteScheduleResult::rejected( 'scheduler_disabled' );
		}

		if ( ! $automation->canAcceptNewRequests() ) {
			return ExecuteScheduleResult::rejected( 'cannot_accept_new_requests' );
		}

		$trace = TraceModel::create();
		$trace->register( $automation )
		      ->setRequest( $context->getRequestParams(), $context->getRequestQuery() );

		if (
			$automation->isAutomationMode( AutomationMode::QUEUED )
			&& ( ! $automation->canRunNow() || $automation->isScheduled() )
		) {
			$trace->setStatus( TraceStatus::QUEUED );
			$trace->save( true );

			return ExecuteScheduleResult::queued( (int) $trace->getId() );
		}

		$stamps = $this->addDefaultDelayStamp( $automation, $stamps );

		$traceId = $this->persistAndGetTraceId( $trace );
		$params  = $context->getRequestParams();
		$query   = $context->getRequestQuery();

		$this->messageBus->dispatch( new AutomationBatch( $automation->getId(), $traceId, $params, $query ), $stamps );

		return ExecuteScheduleResult::dispatched( $traceId );
	}


	public function scheduleNextQueuedTrace( AutomationModel $automation ): ExecuteScheduleResult
	{
		// A mode switch should only affect new incoming requests, not existing queued ones.
		if ( ! $automation->getId() || $automation->hasActiveRuns( false ) ) {
			return ExecuteScheduleResult::skipped( 'active_run_present' );
		}

		$queued = TraceModel::getRepository()->findBy(
			[ 'automation' => $automation->getId(), 'status' => TraceStatus::QUEUED->value ],
			[ 'created' => 'ASC', 'id' => 'ASC' ],
			1
		);

		if ( empty( $queued[0] ) ) {
			return ExecuteScheduleResult::skipped( 'no_queued_trace' );
		}

		$trace = TraceModel::get( $queued[0] );
		$trace->setStatus( TraceStatus::SCHEDULED );
		$trace->save( true );

		$queuedRequest = $trace->getRequest();
		$params        = (array) ( $queuedRequest['params'] ?? [] );
		$query         = (array) ( $queuedRequest['query'] ?? [] );

		$this->messageBus->dispatch( new AutomationBatch( $automation->getId(), $trace->getId(), $params, $query ) );

		return ExecuteScheduleResult::dispatched( (int) $trace->getId() );
	}

	public function scheduleNextIterationTrace( AutomationModel $automation, ExecuteContext $context, array $stamps = [] ): ExecuteScheduleResult
	{
		$trace = $context->getTrace();
		if ( ! $trace ) {
			return ExecuteScheduleResult::rejected( 'missing_trace' );
		}

		$trace->setStatus( TraceStatus::SCHEDULED );
		$trace->save( true );

		$stamps = $this->addDefaultDelayStamp( $automation, $stamps );

		$traceId = $this->persistAndGetTraceId( $trace );
		$params  = $context->getRequestParams();
		$query   = $context->getRequestQuery();

		$this->messageBus->dispatch( new AutomationBatch( $automation->getId(), $traceId, $params, $query ), $stamps );

		return ExecuteScheduleResult::dispatched( $traceId );
	}

	private function addDefaultDelayStamp( AutomationModel $automation, array $stamps ): array
	{
		if ( empty( $stamps ) ) {
			$delay = $automation->getInterval();
			if ( $delay ) {
				$stamps[] = new DelayStamp( $delay * 1000 );
			}
		}

		return $stamps;
	}

	private function persistAndGetTraceId( TraceModel $trace ): int
	{
		if ( ! $trace->getId() ) {
			$trace->setStatus( TraceStatus::SCHEDULED );
			$trace->save( true );
		}

		return $trace->getId() ?? 0;
	}
}

