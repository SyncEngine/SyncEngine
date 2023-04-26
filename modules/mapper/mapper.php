<?php

namespace modules\mapper;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class mapper extends AbstractController
{
	public function Start($config, $datafields)
	{
		$mapper = $config['map'];

		foreach ( $mapper as $map ) {
			if ( isset( $datafields[ $map['from'] ] ) ) {
				$datafields[ $map['to'] ] = $datafields[ $map['from'] ];
				unset( $datafields[ $map['from'] ] );
			}
		}

		return $datafields;
	}
}
