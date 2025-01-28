<?php

namespace SyncEngine\Service\Data;

use SyncEngine\Exception\InvalidException;
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
			$current['count'] = $current->get( 'count', 1 ) + 1;
			$current->ksort();

			return $this;
		}

		$this->traverse[] = $ref;

		$node = $this->getCurrentNode();

		if ( $node ) {
			$node['count'] = $node->get( 'count', 1 ) + 1;
			$node['time_reenter'] = microtime( true );
			$node['memory_reenter'] = memory_get_usage();
			$node->ksort();

			return $this;
		}

		$node = TraceNode::create( $model, $type );

		$node['count']         = 1;
		$node['node']          = implode( '.', $this->traverse );
		$node['time_enter']    = microtime( true );
		$node['time_leave']    = null;
		$node['time_duration'] = 0;
		$node['memory_enter']  = memory_get_usage();
		$node['memory_leave']  = null;
		$node['memory_peak']   = 0;
		$node['memory_total']  = 0;

		$node->ksort();

		$this->addTrace( $node->getRef(), $node, $current );

		return $this;
	}

	public function leaveTrace( $model ): static
	{
		$ref     = TraceNode::parseRef( $model );
		$current = $this->getCurrentNode();

		if ( $current && $ref === $current->getRef() ) {

			$current['time_leave'] = microtime( true );
			$current['time_duration'] += $current['time_leave'] - ( $current['time_reenter'] ?? $current['time_enter'] );
			unset( $current['time_reenter'] );

			$current['memory_leave'] = memory_get_usage();
			$memory = $current['memory_leave'] - ( $current['memory_reenter'] ?? $current['memory_enter'] );
			if ( $memory > 0 ) {
				$current['memory_total'] += $memory;
				if ( $memory > $current['memory_peak'] ) {
					$current['memory_peak'] = $memory;
				}
			}

			array_pop( $this->traverse );
		}

		// @todo Log/Throw if an incorrect current model/node is passed?

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

	public function getTraversedNodes(): array
	{
		return $this->traverse;
	}

	public function restoreTraversedNodes( array $nodes ): static
	{
		$node = $this->getNode( $nodes );

		if ( $node instanceof TraceNode ) {
			$this->traverse = $nodes;
		}

		throw new InvalidException( 'Node traversal does not exist.' );
	}

	public function resetTraversedNodes(): static
	{
		$this->traverse = [];//[ $this->iteration ];

		return $this;
	}
}
