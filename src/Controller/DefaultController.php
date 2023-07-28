<?php

namespace App\Controller;

use App\Service\ClassFinder;
use App\Service\ModelNormalizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends AbstractController
{
	private static $_baseEntityManager;
	private static $_classFinder;

	public function __construct( EntityManagerInterface $entityManager, ClassFinder $classFinder )
	{
		self::$_baseEntityManager = $entityManager;
		self::$_classFinder = $classFinder;
	}

	/**
	 * @return EntityManagerInterface
	 */
	public static function getEntityManager(): EntityManagerInterface
	{
		return self::$_baseEntityManager;
	}

	/**
	 * @return ClassFinder
	 */
	public static function getClassFinder(): ClassFinder
	{
		return self::$_classFinder;
	}

	public function json( mixed $data, int $status = 200, array $headers = [], array $context = [] ): JsonResponse
	{
		return parent::json( ( new ModelNormalizer() )->normalize( $data ), $status, $headers, $context );
	}

	public function slugify( $text )
	{
		// replace non letter or digits by -
		$text = preg_replace( '~[^\pL\d]+~u', '-', $text );

		// transliterate
		$text = iconv( 'utf-8', 'us-ascii//TRANSLIT', $text );

		// remove unwanted characters
		$text = preg_replace( '~[^-\w]+~', '', $text );

		// trim
		$text = trim( $text, '-' );

		// remove duplicated - symbols
		$text = preg_replace( '~-+~', '-', $text );

		// lowercase
		$text = strtolower( $text );

		if ( empty( $text ) ) {
			return 'n-a';
		}

		return $text;
	}
}
