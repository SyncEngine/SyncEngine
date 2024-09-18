<?php

namespace SyncEngine\Service\Trace;

use SyncEngine\Model\TraceModel;
use SyncEngine\Service\Data\TraceData;
use SyncEngine\Service\ExecuteContext;
use SyncEngine\Service\ResourceData;

class TraceContext extends ResourceData
{
	public static function create( $resource = [] ): static
	{
		$context = new static();

		if ( $resource instanceof ExecuteContext ) {
			$flow = $resource->getCurrentFlow();
			if ( $flow ) {
				$context['flow'] = $flow->getId();
			}

			$step = $resource->getCurrentStep();
			if ( $step ) {
				$context['step'] = $step->getId();
			}

			$task = $resource->getCurrentTask();
			if ( $task ) {
				$context['task'] = $task->getClassLocator();
			}

			$resource = $resource->getTrace()?->getCurrentTrace();
		}

		if ( $resource instanceof TraceModel ) {
			$resource = $resource->getCurrentTrace();
		}

		if ( $resource instanceof TraceData ) {
			$context['node']  = $resource->getCurrentNode()?->getKey();
		}

		return $context;
	}
}
