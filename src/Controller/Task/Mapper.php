<?php

namespace App\Controller\Task;

use App\Controller\ModuleController;

class Mapper extends ModuleController
{
	public function executeStep( array $config, array $data ): array
	{
		$mapper = $config['map'];

		foreach ( $mapper as $map ) {
			if ( isset( $data[ $map['from'] ] ) ) {
				$data[ $map['to'] ] = $data[ $map['from'] ];
				unset( $data[ $map['from'] ] );
			}
		}

		return $data;
	}
}
