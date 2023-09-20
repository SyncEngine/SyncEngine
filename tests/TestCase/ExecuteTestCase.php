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

	public function setContext( AutomationModel $automation ): void
	{
		$execute = static::getContainer()->get( Execute::class );
		$this->_executeContext = new ExecutionContext( $automation, $execute );
	}

	public function getContext( $automation = null ): ExecutionContext
	{
		if ( $automation ) {
			$automation = AutomationModel::get( $automation );
		}

		if ( ! isset( $this->_executeContext ) ) {

			if ( ! $automation ) {
				$automation = new AutomationModel( new Automation() );
			}

			$this->setContext( $automation );

		} elseif ( $automation && $automation->getId() !== $this->_executeContext->getAutomation()->getId() ) {

			$this->setContext( $automation );
		}

		return $this->_executeContext;
	}
}
