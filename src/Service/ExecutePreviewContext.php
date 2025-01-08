<?php

namespace SyncEngine\Service;

use Doctrine\ORM\EntityManagerInterface;
use SyncEngine\Model\Abstract\EntityModel;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Service\Sandbox\EntityManagerSandbox;

class ExecutePreviewContext extends ExecuteContext
{
	public function initScope( EntityModel $scope ): void
	{
		if ( $scope instanceof AutomationModel ) {
			$this->automation = $scope;
			$this->variables  = array_replace( $scope->getVariables(), $this->getVariables() );
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

	public function getEntityManager(): EntityManagerInterface
	{
		$em = parent::getEntityManager();

		if ( ! $em instanceof EntityManagerSandbox ) {
			throw new \ErrorException( 'Invalid use of ' . $em::class . ' while executing in sandboxed mode.' );
		}

		return $em;
	}
}
