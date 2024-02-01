<?php

namespace SyncEngine\Entity;

use Doctrine\ORM\Mapping as ORM;
use SyncEngine\Entity\Abstract\EngineEntity;
use SyncEngine\Repository\StepRepository;

#[ORM\Entity( repositoryClass: StepRepository::class )]
class Step extends EngineEntity
{
}
