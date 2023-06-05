<?php

namespace App\Controller;

use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;

class DefaultController extends AbstractController
{
	public function slugify($text)
	{
		// replace non letter or digits by -
		$text = preg_replace('~[^\pL\d]+~u', '-', $text);

		// transliterate
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);

		// trim
		$text = trim($text, '-');

		// remove duplicated - symbols
		$text = preg_replace('~-+~', '-', $text);

		// lowercase
		$text = strtolower($text);

		if (empty($text)) {
			return 'n-a';
		}
		return $text;
	}

	/**
	 * @return EntityManagerInterface
	 */
	public static function getEntityManager(): EntityManagerInterface
	{
		static $doctrine;
		if ( ! $doctrine ) {
			$doctrine = self::getKernel()->getContainer()->get('doctrine');
		}
		return $doctrine->getManager();
	}

	/**
	 * @param  string  $namespace
	 * @return array
	 */
	public static function getClassesInNamespace( string $namespace ): array
	{
		$classes = [];
		$finder  = new Finder();
		$dir     = trim( str_replace( [ '\\', "\\", "/" ], DIRECTORY_SEPARATOR, $namespace ), "\/\\" );

		if ( str_starts_with( $dir, 'App' ) ) {
			$dir = 'src' . substr( $dir , 3 );
		}

		$path = self::getRootDir( true ) . $dir;

		if ( ! is_dir( $path ) ) {
			return [];
		}

		$finder->files()->in( $path )->name('*.php');

		foreach ( $finder as $file ) {
			$file_name  = $file->getFilenameWithoutExtension();
			$class_name = rtrim( $namespace, '\\' ) . '\\' . $file_name;

			if ( class_exists( $class_name ) ) {
				try {
					$classes[] = $class_name;
				} catch ( \Throwable $e ) {
					// @todo Notice?
					continue;
				}
			}
		}

		return $classes;
	}

	/**
	 * @param  string  $dir
	 * @return array
	 */
	public static function getClassesInDir( string $dir ): array
	{
		$classes   = [];
		$finder    = new Finder();
		$namespace = str_replace( [  '\\', "\\", "/", DIRECTORY_SEPARATOR ], "\\", $dir );
		$path      = self::getRootDir( true ) . trim( $dir, "\/\\" );

		if ( str_starts_with( $dir, 'src' ) ) {
			$namespace = 'App' . substr( $namespace , 3 );
		}

		if ( ! is_dir( $path ) ) {
			return [];
		}

		$finder->files()->in( $path )->name('*.php');

		foreach ( $finder as $file ) {
			$file_name  = $file->getFilenameWithoutExtension();
			$class_name = rtrim( $namespace, '\\' ) . "\\" . $file_name . "\\" . $file_name;

			if ( class_exists( $class_name ) ) {
				try {
					$classes[] = $class_name;
				} catch ( \Throwable $e ) {
					// @todo Notice?
					continue;
				}
			}
		}

		return $classes;
	}

	public static function getRootDir( bool $trail = false ): string
	{
		return self::getKernel()->getProjectDir() . ( ( $trail ) ? DIRECTORY_SEPARATOR : '' );
	}

	public static function getKernel(): Kernel
	{
		return $GLOBALS['app'];
	}

	public function __get(string $property)
	{
		$getter = array($this, 'get_' . $property);
		if (is_callable($getter)) {
			return call_user_func($getter);
		}
		return $this->$property ?? null;
	}
}
