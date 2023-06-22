<?php

namespace App\Model\Trait;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

trait Entity
{
	protected object $entity;

	public function __construct( $entity ) {
		$this->entity = $entity;
	}

	public function getEntity(): object
	{
		return $this->entity;
	}

	public function persist( EntityManagerInterface $entityManager ): void
	{
		$entityManager->persist( $this->entity );
	}

	public function __call( string $name, array $arguments )
	{
		if ( ! is_callable( array( $this->entity, $name ) ) ) {
			throw new Exception();
		}
		call_user_func_array( array( $this->entity, $name ), $arguments );
	}
}
