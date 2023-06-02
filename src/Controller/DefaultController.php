<?php

namespace App\Controller;

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

	public function getClassesInNamespace(string $namespace): array
	{
		$finder = new Finder();
		$finder->files()->in(__DIR__)->name('*.php');
		foreach ($finder as $file) {
			$class_name = rtrim($namespace, '\\') . '\\' . $file->getFilenameWithoutExtension();
			if (class_exists($class_name)) {
				try {
					$task[] = new $class_name();
				} catch (\Throwable $e) {
					continue;
				}
			}
		}

		return $task ?? [];
	}

	public function getClassesInFolder(string $dir): array
	{
		$classes = [];
		$finder  = new Finder();
		$finder->files()->in( '../' . $dir )->name('*.php');

		foreach ( $finder as $file ) {
			$namespace  = str_replace( '/', "\\", $dir );
			$class_name = $namespace . "\\" . $file->getFilenameWithoutExtension() . "\\" . $file->getFilenameWithoutExtension();
			if ( class_exists( $class_name ) ) {
				try {
					$classes = new $class_name();
				} catch ( \Throwable $e ) {
					// @todo Error or log.
					continue;
				}
			}
		}

		return $classes;
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
