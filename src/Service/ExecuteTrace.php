<?php

namespace SyncEngine\Service;

class ExecuteTrace extends ResourceData
{
	public function addTrace( $message, ExecutionContext $context, $model = null, $data = [] ): void
	{
		$key = [];
		foreach ( $context->getAncestors() as $ancestor ) {
			$key[] = $ancestor->getIndex();
		}
		$key[] = $context->getIndex();

		$trace = (array) $this->get( $key, [] );

		if ( $model ) {

			if ( is_array( $model ) ) {
				$name = $model['_label'] ?? '';
				$ref  = $model['_ref'];
			} else {
				$name = $model->getName() ?? '';
				$ref  = $model->getRef();
			}

			$label = $name ? $name . ' (' . $ref . ')' : $ref;
			$message .= ': ' . $label . ' | ' . count( $data );
		}

		$trace[] = $message;

		$this->set( $trace, $key );
	}
}
