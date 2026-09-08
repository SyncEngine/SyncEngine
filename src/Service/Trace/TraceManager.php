<?php

namespace SyncEngine\Service\Trace;

use SyncEngine\Entity\Trace;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Model\Enum\TraceStatus;
use SyncEngine\Model\TraceModel;

class TraceManager
{
	public function pruneTraces( AutomationModel $automation = null, bool $persist = true ): true
	{
		// Limit number of traces by user setting.
		$retention = $automation->getTraceHistoryRetention();

		// Keep in-flight queue/schedule traces; only prune finished/history traces.
		$traces = $automation->getTraces()->filter( function ( Trace $trace ) {
			return ! in_array( $trace->getStatus(), [ TraceStatus::SCHEDULED->value, TraceStatus::QUEUED->value ], true );
		} );

		// Group traces by matching config key.
		$groups = [];
		foreach ( $traces as $trace ) {
			$status = $trace->getStatus();
			if ( isset( $retention[ $status ] ) ) {
				$groups[ $status ][] = $trace;
			} else {
				$groups['default'][] = $trace;
			}
		}

		// Apply limits to each group.
		foreach ( $groups as $key => $groupTraces ) {
			$rule = $retention[ $key ];

			if ( is_int( $rule ) ) {
				$rule = [ 'limit' => $rule ];
			}

			if ( 'days' === $rule['unit'] && ! empty( $rule['limit'] ) ) {
				$cutoff = time() - ( $rule['limit'] * 86400 ); // 86400 seconds in a day
				foreach ( $groupTraces as $trace ) {
					if ( $trace->getCreated()->getTimestamp() < $cutoff ) {
						TraceModel::create( $trace )->removeTraceFiles();
						$automation->removeTrace( $trace );
					}
				}
			} elseif ( $rule['limit'] < \count( $groupTraces ) ) {
				usort( $groupTraces, fn( $a, $b ) => $b->getCreated() <=> $a->getCreated() );
				foreach ( array_slice( $groupTraces, $rule['limit'] ) as $trace ) {
					TraceModel::create( $trace )->removeTraceFiles();
					$automation->removeTrace( $trace );
				}
			}
		}

		if ( $persist ) {
			$automation->save( true );
		}

		return true;
	}
}
