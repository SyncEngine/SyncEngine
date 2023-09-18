<?php

namespace App\Tests\Task;

class TemplateTest extends TaskTestCase
{
	protected string $_task = 'Template';

	public function testTask(): void
	{
		$this->bootstrap();

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
