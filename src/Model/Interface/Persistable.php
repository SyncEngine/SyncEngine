<?php

namespace SyncEngine\Model\Interface;

use Doctrine\ORM\EntityManagerInterface;

interface Persistable
{
	public function getEntity(): object;
	public function update( $flush = false, ?EntityManagerInterface $entityManager = null ): void;
	public function save( $flush = false, ?EntityManagerInterface $entityManager = null ): void;
	public function persist( $flush = false, ?EntityManagerInterface $entityManager = null ): void;
	public function delete( $flush = false, ?EntityManagerInterface $entityManager = null ): bool;
	public function normalize( $dependencies = false, $dependents = false ): array;
}
