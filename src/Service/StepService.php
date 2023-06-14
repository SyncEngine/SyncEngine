<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Entity\Step;

class StepService
{
	public function execute( Step $step, $data, $originalData = array() ): array
	{
		return $this->executeConfig( $step->getConfig(), $data );
	}

	public function executeConfig(array $config, $data): array
	{
		$tasks = $config['tasks'] ?? [];
		if ($tasks) {
			foreach ($tasks as $task) {
				if (!empty($task['module'])) {
					$data = $this->executeModule($task["module"], $task, $data);
				} else {
					$data = $this->executeTask($task, $data);
				}
			}
		}
		return $data;
	}

	public function executeTask(array $config, $data): array
	{
		$task = $config['type'] ?? '';
		if ($task) {
			$task = ( new TaskService() )->getTask( $task );
			if ( $task ) {
				$data = $task->execute($config, $data);
			}
		}
		return $data;
	}

	public function executeModule(string $moduleName, array $config, array $data): array
	{
		return ModuleService::getModule($moduleName)->executeConfig($config, $data);
	}

	public function getStep( int $id ): Step|null
	{
		return DefaultController::getEntityManager()->getRepository( Step::class )->findOneBy( [ 'id' => $id ] );
	}

	public function getSteps(): array
	{
		return DefaultController::getEntityManager()->getRepository( Step::class )->findAll();
	}
}
