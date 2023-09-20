<?php

namespace App\Tests\Task;

use App\Tests\TestCase\TaskTestCase;

class TemplateTest extends TaskTestCase
{
	protected string $_task = 'Template';

	public function testAdd(): void
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

	public function testCalc(): void
	{
		$task = $this->getTask();

		$config = [
			'template' => "
				{% set incl = data.price * 1.21 %}
				{% set data = data|merge({ price_incl: incl }) %}
			",
		];

		$price = 12.35;

		$data = [
			'price' => $price,
		];

		$incl = $price * 1.21;

		$result = $task->execute( $config, $this->getContext(), $data );

		$this->assertArrayHasKey( 'price_incl', $result );
		$this->assertEquals( $incl, $result['price_incl'] );
	}
}
