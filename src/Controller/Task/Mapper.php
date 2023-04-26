<?php

namespace App\Controller\Task;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class Mapper extends AbstractController
{
	public function executeStep( $config, $data )
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
