<?php

namespace SyncEngine\Entity;

use Doctrine\ORM\Mapping as ORM;
use SyncEngine\Entity\Abstract\EngineEntity;
use SyncEngine\Repository\ConnectionRepository;

#[ORM\Entity( repositoryClass: ConnectionRepository::class )]
class Connection extends EngineEntity
{
}
