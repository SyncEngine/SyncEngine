<?php

namespace SyncEngine\Service;

use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\AutomationModel;

class ExecutePreviewContext extends ExecuteContext
{
	public function initScope( EntityModel $scope ): void
	{
		if ( $scope instanceof AutomationModel ) {
			$this->automation = $scope;
			$this->variables  = array_replace( $this->variables, $scope->getVariables() );
		}
	}

	public function isPreview( string $type = '' ): bool
	{
		$execute = $this->getExecuteService();

		if ( $execute instanceof ExecutePreview ) {
			return $execute->getMode() === $type;
		}

		return false;
	}
}
