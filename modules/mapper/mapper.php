<?php

namespace modules\mapper;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class Mapper extends AbstractController
{
	public function executeStep($config, $datafields)
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
