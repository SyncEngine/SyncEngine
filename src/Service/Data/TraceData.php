<?php

namespace SyncEngine\Service\Data;

use SyncEngine\Service\ResourceData;
use SyncEngine\Service\Trace\Enum\TraceLogType;
use SyncEngine\Service\Trace\TraceLog;
use SyncEngine\Service\Trace\TraceNode;

class TraceData extends ResourceData
{
	private array $traverse = [];

	public function addLog( TraceLog $message, string $name = 'Log' ): static
	{
		if ( TraceLogType::isValid( $name ) ) {
			$name .= ': ' . microtime(true);
		}

		$name = $this->createUniqueKey( $name, $this->getCurrentTraces() );

		$this->addTrace( $name, $message );

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

		$current = $this->getCurrentNode();

		// Check if it is the same loop.
		if ( $current && $ref === $current->getRef() ) {
			if ( ! isset( $current['count'] ) ) {
				$current['count'] = 1;
			}
			$current['count']++;
			$current->ksort();

			return $this;
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

		$this->addTrace( $node->getRef(), $node, $current );

		return $this;
	}

	public function leaveTrace( $model ): static
	{
		$ref     = TraceNode::parseRef( $model );
		$current = $this->getCurrentNode();

		if ( $current && $ref === $current->getRef() ) {

			$current['time_leave'] = microtime(true);

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

	public function addTrace( string $name, $data, ?TraceNode $node = null ): static
	{
		$current = $node ?: $this->getCurrentNode() ?: $this;

		$trace = $current->get( 'trace', [] );
		$trace[ $name ] = $data;

		$current->set( $trace, 'trace' );

		return $this;
	}

	public function getCurrentTraces(): iterable
	{
		$current = $this->getCurrentNode() ?: $this;

		return $current->get( 'trace', [] );
	}

	public function getCurrentNode(): ?TraceNode
	{
		return $this->getNode( $this->traverse );
	}

	public function getNode( $traverse ): ?TraceNode
	{
		$key = $this->parseKey( $traverse );

		$node = $this->get( 'trace.' . implode( '.trace.', (array) $key ) );

		if ( $node instanceof TraceNode ) {
			return $node;
		}

		return $node ? new TraceNode( $node ) : null;
	}

	public function resetTraversal(): static
	{
		$this->traverse = [];//[ $this->iteration ];

		return $this;
	}
}
