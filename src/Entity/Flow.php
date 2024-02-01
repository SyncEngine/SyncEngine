<?php

namespace SyncEngine\Entity;

use Doctrine\ORM\Mapping as ORM;
use SyncEngine\Entity\Abstract\EngineEntity;
use SyncEngine\Repository\FlowRepository;

#[ORM\Entity( repositoryClass: FlowRepository::class )]
class Flow extends EngineEntity
{
}
