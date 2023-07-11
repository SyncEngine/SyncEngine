<?php

namespace App\Service;

use App\Controller\DefaultController;
use App\Entity\Dataset;
use App\Model\DatasetModel;

class DatasetService
{
	public static function getDataset( Dataset|int $dataset ): DatasetModel|null
	{
		if ( ! $dataset instanceof Dataset ) {
			$dataset = DefaultController::getEntityManager()
			                            ->getRepository( Dataset::class )
			                            ->findOneBy( [ 'id' => $dataset ] );
		}
		if ( $dataset ) {
			return new DatasetModel( $dataset );
		}

		return null;
	}

	public static function getDatasets(): array
	{
		$datasets = DefaultController::getEntityManager()->getRepository( Dataset::class )->findAll();
		$models   = [];
		foreach ( $datasets as $dataset ) {
			$models[ $dataset->getId() ] = new DatasetModel( $dataset );
		}

		return $models;
	}
}
