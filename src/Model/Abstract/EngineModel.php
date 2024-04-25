<?php

namespace SyncEngine\Model\Abstract;

use SyncEngine\Entity\Abstract\EngineEntity;
use SyncEngine\Model\Interface\Configurable;
use SyncEngine\Model\Interface\Exportable;
use SyncEngine\Model\Trait\Config;
use SyncEngine\Model\Trait\Ref;

/**
 * @template T of EngineEntity
 */
abstract class EngineModel extends EntityModel implements Exportable, Configurable
{
	use Ref;
	use Config;

	public function export(): array
	{
		return $this->getContainer()->get( 'ModelExporter' )->export( $this );
	}
}
