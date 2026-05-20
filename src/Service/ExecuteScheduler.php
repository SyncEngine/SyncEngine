<?php

namespace SyncEngine\Service;

use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use SyncEngine\Messenger\Message\AutomationBatch;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\Enum\AutomationMode;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Model\TraceModel;

class ExecuteScheduler
{
	public function __construct(
		private readonly MessageBusInterface $messageBus,
	) {}

	public function schedule( AutomationModel $automation, ExecuteContext $context, array $stamps = [] ): void
	{
		$trace = $context->getTrace();
		$new   = ! $trace;

		if ( ! $trace ) {
			$trace = TraceModel::create();
			$trace->register( $automation )
			      ->setRequest( $context->getRequestParams(), $context->getRequestQuery() );
		}

		if (
			$new && $automation->isAutomationMode( AutomationMode::QUEUED )
			&& ( ! $automation->canRunNow() || $automation->isScheduled() )
		) {
			$trace->setStatus( TraceStatus::QUEUED );
			$trace->save( true );
			return;
		}

		if ( empty( $stamps ) ) {
			$delay = $automation->getInterval();
			if ( $delay ) {
				$stamps[] = new DelayStamp( $delay * 1000 );
			}
		}

		if ( ! $trace->getId() ) {
			$trace->setStatus( TraceStatus::SCHEDULED );
			$trace->save( true );
		}

		$traceId = $trace->getId() ?? 0;
		$params  = $context->getRequestParams();
		$query   = $context->getRequestQuery();

		$this->messageBus->dispatch( new AutomationBatch( $automation->getId(), $traceId, $params, $query ), $stamps );
	}

	public function scheduleNextQueuedTrace( AutomationModel $automation ): void
	{
		// A mode switch should only affect new incoming requests, not existing queued ones.
		if ( ! $automation->getId() || $automation->hasActiveRuns( false ) ) {
			return;
		}

		$queued = TraceModel::getRepository()->findBy(
			[ 'automation' => $automation->getId(), 'status' => TraceStatus::QUEUED->value ],
			[ 'created' => 'ASC', 'id' => 'ASC' ],
			1
		);

		if ( empty( $queued[0] ) ) {
			return;
		}

		$trace = TraceModel::get( $queued[0] );

		$queuedRequest = $trace->getRequest();
		$params        = (array) ( $queuedRequest['params'] ?? [] );
		$query         = (array) ( $queuedRequest['query'] ?? [] );

		$this->messageBus->dispatch( new AutomationBatch( $automation->getId(), $trace->getId(), $params, $query ) );
	}
}

