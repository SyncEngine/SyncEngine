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

abstract class ExecuteTestCase extends KernelTestCase
{
	protected ExecutionContext $_executeContext;

	public function setUp(): void
	{
		parent::setUp();

		// Initialize globals.
		if ( ! DefaultController::getClassFinder() ) {
			static::getContainer()->get( DefaultController::class );
		}
	}

	public function getContext( $automation = null ): ExecutionContext
	{
		if ( ! isset( $this->_executeContext ) ) {
			$execute = static::getContainer()->get( Execute::class );

			if ( ! $automation ) {
				$automation = new AutomationModel( new Automation() );
			} else {
				$automation = AutomationModel::get( $automation );
			}

			$this->_executeContext = new ExecutionContext( $automation, $execute );

		} elseif ( $automation ) {
			$automation = AutomationModel::get( $automation );

			if ( $automation->getId() !== $this->_executeContext->getAutomation()->getId() ) {

				$execute = static::getContainer()->get( Execute::class );

				$this->_executeContext = new ExecutionContext( $automation, $execute );
			}
		}

		return $this->_executeContext;
	}
}
