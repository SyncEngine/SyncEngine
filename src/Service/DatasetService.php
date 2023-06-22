<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Entity\Dataset;

class DatasetService
{
	public static function getDataset( int $id ): Dataset|null
	{
		return DefaultController::getEntityManager()->getRepository( Dataset::class )->findOneBy( [ 'id' => $id ] );
	}

	public static function getDatasets(): array
	{
		return DefaultController::getEntityManager()->getRepository( Dataset::class )->findAll();
	}
}
