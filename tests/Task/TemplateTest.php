<?php

namespace SyncEngine\Tests\Task;

use SyncEngine\Tests\TestCase\TaskTestCase;

class TemplateTest extends TaskTestCase
{
	protected string $_task = 'Template';

	public function testAdd(): void
	{
		$config = [
			'template' => "{% set data = data|merge({ template_test: 'yay!' }) %}",
		];

		$data = [
			'name' => 'test',
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertArrayHasKey( 'template_test', $result );
		$this->assertContains( 'yay!', $result );
	}

	public function testCalc(): void
	{
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

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertArrayHasKey( 'price_incl', $result );
		$this->assertEquals( $incl, $result['price_incl'] );
	}

	public function testAdvancedCalc(): void
	{
		$config = [
			'template' => "

{% set result = [] %}
{% for row in data %}

    {% set price = row.price|replace({',':'.'}) %}

    {% if price < 5 %}

        {% set price = price|round(1, 'floor') %}

    {% elseif price < 10 %}

        {% set floor = price|round(0, 'floor') %}
        {% set fraction = price - floor %}

        {% if fraction < 0.25 %}
            {% set price = floor %}
        {% elseif fraction < 0.50 %}
            {% set price = floor + 0.25 %}
        {% elseif fraction < 0.75 %}
            {% set price = floor + 0.50 %}
        {% else %}
            {% set price = floor + 0.75 %}
        {% endif %}

    {% elseif price < 50 %}

        {% set floor = price|round(0, 'floor') %}
        {% set fraction = price - floor %}

        {% if fraction < 0.50 %}
            {% set price = floor %}
        {% else %}
            {% set price = floor + 0.50 %}
        {% endif %}

    {% else %}

        {% set price = price|round(0, 'floor') %}

    {% endif %}

    {% set price = price|number_format(2, ',', '') %}
    {% set row = row|merge( { price: price } ) %}
    {% set result = result|merge( { (loop.index0): row } ) %}

{% endfor %}
{% set data = result %}

			"
		];

		$data = [
			[
				"Keep me" => 'Yay!',
				"price" => "1,98",
			],
			[
				"price" => "2,97",
			],
			[
				"price" => "3,59",
			],
			[
				"Keep me" => 'Foo!',
				"price" => "14,00",
			],
			[
				"price" => "21,01",
			],
			[
				"Keep me" => [
					'Foo' => 'Bar',
				],
				"price" => "25,52",
			],
			[
				"price" => "160,39",
			],
			[
				"Keep me" => 'Bar!',
				"price" => "240,58",
			],
			[
				"price" => "291,10",
			]
		];

		$expected = [
			[
				"Keep me" => 'Yay!',
				"price" => "1,90",
			],
			[
				"price" => "2,90",
			],
			[
				"price" => "3,50",
			],
			[
				"Keep me" => 'Foo!',
				"price" => "14,00",
			],
			[
				"price" => "21,00",
			],
			[
				"Keep me" => [
					'Foo' => 'Bar',
				],
				"price" => "25,50",
			],
			[
				"price" => "160,00",
			],
			[
				"Keep me" => 'Bar!',
				"price" => "240,00",
			],
			[
				"price" => "291,00",
			]
		];

		$result = $this->execute( $config, $this->getContext(), $data );

		$this->assertEquals( $expected, $result );
	}
}
