<?php

namespace App\Model\Trait;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

trait Entity
{
	protected object $entity;

	public function getEntity(): object
	{
		return $this->entity;
	}

	public function update( EntityManagerInterface $entityManager, $flush = false ): void
	{
		if ( $this->entity->getId() ) {
			$this->persist( $entityManager, $flush );
		}
	}

	public function persist( EntityManagerInterface $entityManager, $flush = false ): void
	{
		$entityManager->persist( $this->entity );
		if ( $flush ) {
			$entityManager->flush();
		}
	}

	public function __call( string $name, array $arguments )
	{
		if ( ! is_callable( [ $this->entity, $name ] ) ) {
			throw new Exception();
		}

		return call_user_func_array( [ $this->entity, $name ], $arguments );
	}
}
