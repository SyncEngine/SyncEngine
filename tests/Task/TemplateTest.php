<?php

namespace App\Tests\Task;

use App\Controller\DefaultController;
use App\Entity\Automation;
use App\Model\AutomationModel;
use App\Model\TaskModel;
use App\Service\Execute;
use App\Service\ExecutionContext;
use App\Service\Tasks;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TemplateTest extends KernelTestCase
{
	public function getTask(): ?TaskModel
	{
		return Tasks::getTask( 'Template' );
	}

	public function getContext(): ExecutionContext
	{
		$execure = static::getContainer()->get( Execute::class );
		$automation = new AutomationModel( new Automation() );

		return new ExecutionContext( $automation, $execure );
	}

	public function testTask(): void
	{
		$task = $this->getTask();

		$config = [
			'template' => "{% set data = data|merge({ template_test: 'yay!' }) %}",
		];

		$data = [
			'name' => 'test',
		];

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertArrayHasKey( 'template_test', $result );
		$this->assertContains( 'yay!', $result );
	}
}
