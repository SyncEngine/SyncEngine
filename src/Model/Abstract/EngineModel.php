<?php

namespace SyncEngine\Model\Abstract;

use SyncEngine\Controller\Abstract\EntityController;
use SyncEngine\Controller\DefaultController;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Exportable;
use SyncEngine\Model\Interface\Persistable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Model\Trait\Data;
use SyncEngine\Model\Trait\Ref;
use SyncEngine\Repository\Interface\Searchable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

abstract class EngineModel extends EntityModel implements Exportable, Configurable
{
	use Ref;
	use Config;

	public function export(): array
	{
		return $this->getContainer()->get( 'ModelExporter' )->export( $this );
	}
}
