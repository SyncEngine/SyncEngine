<?php

namespace SyncEngine\Tests\Model;


use SyncEngine\Model\TraceModel;
use SyncEngine\Service\Data\ResourceData;
use SyncEngine\Tests\TestCase\BaseTestCase;

class TraceModelTest extends BaseTestCase
{
	public function testTrace(): void
	{
		$traceModel = TraceModel::create();

		$traceModel->start();

		$trace = $traceModel->getCurrentTrace();

		$this->assertArrayHasKey( 'time_start', $trace->normalize() );

		/**
		 * Start trace.
		 */
		$traceModel->enterTrace( 'Foo' );

		$fooNode = $trace->getCurrentNode();

		$this->assertEquals( 'Foo', $fooNode->getRef() );

		/**
		 * One level deep.
		 */
		$traceModel->enterTrace( 'Bar' );

		$barNode = $trace->getCurrentNode();

		$this->assertEquals( 'Bar', $barNode->getRef() );

		$this->assertEquals( 'Foo.Bar', $trace->getCurrentNode()->getKey() );

		$this->assertEquals( $barNode, $trace->get( 'trace.Foo.trace.Bar' ) );

		/**
		 * Go back one level.
		 */
		$traceModel->leaveTrace( 'Bar' );

		$this->assertEquals( $fooNode, $trace->getCurrentNode() );

		$this->assertEquals( $fooNode, $trace->get( 'trace.Foo' ) );

		/**
		 * One level deep.
		 */
		$traceModel->enterTrace( 'Bar' );

		$this->assertEquals( $barNode, $trace->getCurrentNode() );

		$this->assertEquals( 2, $barNode['count'] );

		/**
		 * Log
		 */
		$traceModel->addLog( 'Test Log' );

		$traces = $trace->getCurrentTraces();

		$traces = ResourceData::create( $traces )->normalize();

		$messages = array_column( $traces, 'message' );

		$this->assertTrue( in_array( 'Test Log', $messages, true ) );

	}
}
