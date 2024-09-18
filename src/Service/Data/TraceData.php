<?php

namespace SyncEngine\Service\Data;

use SyncEngine\Service\ResourceData;
use SyncEngine\Service\Trace\TraceLog;
use SyncEngine\Service\Trace\TraceNode;

class TraceData extends ResourceData
{
	private array $traverse = [];

	public function addLog( TraceLog $message, string $name = 'Log' ): static
	{
		$node = $this->getCurrentNode();

		if ( ! isset( $node['trace'] ) ) {
			$node['trace'] = [];
		}

		$name = $this->createUniqueKey( $name . ': ' . microtime(true), $node['trace'] );

		$node['trace'][ $name ] = $message;

		$node->ksort();

		return $this;
	}

	public function addError( TraceLog $message ): static
	{
		$this->addLog( $message, 'Error' );

		return $this;
	}

	public function enterTrace( $model, $type = '' ): static
	{
		$ref = TraceNode::parseRef( $model );

		// Check if it is the same loop.
		$isCurrent = $ref === end( $this->traverse );

		$current = $this->getCurrentNode();

		if ( $isCurrent ) {
			if ( ! isset( $current['count'] ) ) {
				$current['count'] = 1;
			}
			$current['count']++;
			$current->ksort();

			return $this;
		}

		// Make sure a trace exists.
		if ( ! empty( $this->traverse ) && ! isset( $current['trace'] ) ) {
			$current['trace'] = [];
		}

		$this->traverse[] = $ref;

		$node = $this->getCurrentNode();

		if ( $node ) {
			if ( ! isset( $node['count'] ) ) {
				$node['count'] = 1;
			}
			$node['count']++;
			$node->ksort();

			return $this;
		}

		$node = TraceNode::create( $model, $type );

		$node['node']       = implode( '.', $this->traverse );
		$node['time_enter'] = microtime(true);
		$node['time_leave'] = false;

		$node->ksort();
		$current['trace'][ $node->getRef() ] = $node;

		return $this;
	}

	public function leaveTrace( $model ): static
	{
		$ref = TraceNode::parseRef( $model );

		if ( $ref === end( $this->traverse ) ) {

			$current = $this->getCurrentNode();
			if ( ! empty( $current ) ) {
				$current['time_leave'] = microtime(true);
			}

			array_pop( $this->traverse );
		}

		return $this;
	}

	public function createUniqueKey( $key, $array )
	{
		if ( ! isset( $array[ $key ] ) ) {
			return $key;
		}

		$count = 1;
		do {
			$count++;
		} while ( isset( $array[ $key . ' ' . $count ] ) );

		return $key . ' ' . $count;
	}

	public function getCurrentNode(): ?TraceNode
	{
		return $this->getNode( $this->traverse );
	}

	public function getNode( $span ): ?TraceNode
	{
		$key = $this->parseKey( $span );

		$span = $this->get( 'trace.' . implode( '.trace.', (array) $key ) );

		if ( $span instanceof TraceNode ) {
			return $span;
		}

		return $span ? new TraceNode( $span ) : null;
	}

	public function getTraverseKey(): string
	{
		if ( $this->traverse ) {
			return 'trace.' . implode( '.trace.', $this->traverse );
		}

		return 'trace';
	}

	public function resetTraversal(): static
	{
		$this->traverse = [];//[ $this->iteration ];

		return $this;
	}
}
