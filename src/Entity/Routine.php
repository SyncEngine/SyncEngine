<?php

namespace SyncEngine\Entity;

use Doctrine\ORM\Mapping as ORM;
use SyncEngine\Entity\Abstract\EngineEntity;
use SyncEngine\Repository\RoutineRepository;

#[ORM\Entity( repositoryClass: RoutineRepository::class )]
class Routine extends EngineEntity
{
}
