<?php

namespace App\Tests\TestCase;

use App\Controller\DefaultController;
use App\Entity\Automation;
use App\Model\AutomationModel;
use App\Model\TaskModel;
use App\Service\Execute;
use App\Service\ExecutionContext;
use App\Service\Tasks;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class TaskTestCase extends KernelTestCase
{
	protected string $_task;

	public function bootstrap(): void
	{
		// Initialize globals.
		if ( ! DefaultController::getClassFinder() ) {
			static::getContainer()->get( DefaultController::class );
		}
	}

	public function getTask(): ?TaskModel
	{
		return Tasks::getTask( $this->_task );
	}

	public function getContext(): ExecutionContext
	{
		$execure = static::getContainer()->get( Execute::class );
		$automation = new AutomationModel( new Automation() );

		return new ExecutionContext( $automation, $execure );
	}
}
