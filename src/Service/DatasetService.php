<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Entity\Dataset;
use App\Model\DatasetModel;

class DatasetService
{
	public static function getDataset( int $id ): DatasetModel|null
	{
		$dataset = DefaultController::getEntityManager()->getRepository( Dataset::class )->findOneBy( [ 'id' => $id ] );
		if ( $dataset ) {
			return new DatasetModel( $dataset );
		}
		return null;
	}

	public static function getDatasets(): array
	{
		$datasets = DefaultController::getEntityManager()->getRepository( Dataset::class )->findAll();
		$models = [];
		foreach ( $datasets as $dataset ) {
			$models[ $dataset->getId() ] = new DatasetModel( $dataset );
		}
		return $models;
	}
}
