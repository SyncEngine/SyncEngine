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
		$finder->files()->in(__DIR__)->in('../modules')->name('*.php');
		foreach ($finder as $file) {
			$class_name = rtrim($namespace, '\\') . '\\' . $file->getFilenameWithoutExtension();
			$moduleClass = "modules\\" . $file->getFilenameWithoutExtension() . "\\" . $file->getFilenameWithoutExtension();

			$exsistingClass = false;
			if (class_exists($class_name)) {
				$exsistingClass = $class_name;
			} elseif (class_exists($moduleClass)) {
				$exsistingClass = $moduleClass;
			}

			if ($exsistingClass) {
				try {
					$task[] = new $exsistingClass();
				} catch (\Throwable $e) {
					continue;
				}
			}
		}

		return $task ?? [];
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
