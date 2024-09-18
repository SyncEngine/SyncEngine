<?php

namespace SyncEngine\Tests\TestCase;

use SyncEngine\Entity\Automation;
use SyncEngine\Model\AutomationModel;
use SyncEngine\Service\Execute;
use SyncEngine\Service\ExecuteContext;

abstract class ExecuteTestCase extends BaseTestCase
{
	protected Execute $_execute;
	protected ExecuteContext $_executeContext;

	public function setContext( AutomationModel $automation ): void
	{
		$this->_execute = static::getContainer()->get( Execute::class );
		$this->_executeContext = new ExecuteContext( $this->_execute, $automation );
	}

	public function getExecute(): Execute
	{
		if ( ! isset( $this->_execute ) ) {
			$this->getContext();
		}

		return $this->_execute;
	}

	public function getContext( $automation = null ): ExecuteContext
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
