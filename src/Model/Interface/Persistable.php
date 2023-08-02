<?php

namespace App\Model\Interface;

use Doctrine\ORM\EntityManagerInterface;

interface Persistable
{
	public function getEntity(): object;
	public function update( EntityManagerInterface $entityManager, $flush = false ): void;
	public function persist( EntityManagerInterface $entityManager, $flush = false ): void;
}
