<?php

namespace App\Controller;

use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class EntityController extends DefaultController
{
	public function removeCircularReference( $entity )
	{
		$defaultContext = [
			AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object): string {
				return $object->getId();
			},
		];

		$normalizer = new ObjectNormalizer( null, null, null, null, null, null, $defaultContext );

		return ( new Serializer( [ $normalizer ] ) )->normalize( $entity );
	}
}
